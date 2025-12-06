<?php
require 'assets/includes/admin_config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get username from session or account
$uname = $_SESSION['name'] ?? '';
if (empty($uname)) {
    // Fetch from account if not in session
    if (isset($_SESSION['id'])) {
        $stmt = $pdo->prepare('SELECT username FROM accounts WHERE id = ?');
        $stmt->execute([$_SESSION['id']]);
        $account_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $uname = $account_data['username'] ?? '';
    }
}

// Debug: Check if blog_pdo is available
if (!isset($blog_pdo)) {
    error_log('DATABASE DEBUG - blog_pdo is not set');
    die('Database connection not available');
} else {
    error_log('DATABASE DEBUG - blog_pdo is available');
}

// Debug: Log if form was submitted
error_log('DEBUG - Request method: ' . $_SERVER['REQUEST_METHOD']);
error_log('DEBUG - POST keys: ' . (empty($_POST) ? 'EMPTY' : implode(', ', array_keys($_POST))));
error_log('DEBUG - Is add set? ' . (isset($_POST['add']) ? 'YES' : 'NO'));
error_log('DEBUG - POST add value: ' . (isset($_POST['add']) ? $_POST['add'] : 'NOT SET'));

if (isset($_POST['add'])) {
    // Debug: Log all POST data
    error_log('FORM SUBMITTED - POST data received: ' . print_r($_POST, true));
    error_log('FORM SUBMITTED - FILES data received: ' . print_r($_FILES, true));
    error_log('FORM SUBMITTED - Session data: ' . print_r($_SESSION, true));

    $title = trim($_POST['title']);
    error_log('FORM SUBMITTED - Title after trim: "' . $title . '"');

    // Validate title
    if (empty($title)) {
        $error_message = 'Please enter a title for the post.';
        error_log('FORM SUBMITTED - ERROR: Empty title');
    } else {
        // Validate title length
        if (strlen($title) > 250) {
            $title = substr($title, 0, 250);
        }

        $slug = generateSeoURL($title);
        $active = $_POST['active'] ?? 'No';
        $featured = $_POST['featured'] ?? 'No';
        $category_id = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;
        $content = $_POST['content'];

        // Validate content
        if (empty($content)) {
            // For debugging, allow empty content but log it
            $content = '<p>Post created but content was empty. This indicates TinyMCE content was not saved properly.</p>';
            error_log('FORM SUBMITTED - WARNING: Content was empty, using placeholder content');
        }

        $date = date($settings['date_format']);
        $time = date('H:i');

        $author = $uname;
        $stmt = $blog_pdo->prepare("SELECT id FROM `users` WHERE username = ? LIMIT 1");
        $stmt->execute([$author]);
        $auth = $stmt->fetch(PDO::FETCH_ASSOC);
        $author_id = $auth ? $auth['id'] : 0;

        $image = '';

        if (@$_FILES['image']['name'] != '') {
                $target_dir    = "../../client-dashboard/blog/uploads/posts/";
                $target_file   = $target_dir . basename($_FILES["image"]["name"]);
                $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                $uploadOk = 1;

                // Check if image file is a actual image or fake image
                $check = getimagesize($_FILES["image"]["tmp_name"]);
                if ($check !== false) {
                    $uploadOk = 1;
                } else {
                    $error_message = 'The file is not an image.';
                    $uploadOk = 0;
                }

                // Check file size
                if ($_FILES["image"]["size"] > 10000000) {
                    $error_message = 'Sorry, your file is too large.';
                    $uploadOk = 0;
                }

                if ($uploadOk == 1) {
                    // Keep original filename, add number if duplicate
                    $original_name = pathinfo($_FILES["image"]["name"], PATHINFO_FILENAME);
                    $extension = $imageFileType;
                    $upload_dir = "../../client-dashboard/blog/uploads/posts/";
                    
                    // Sanitize filename: remove special characters, keep alphanumeric, dash, underscore
                    $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $original_name);
                    
                    $filename = $safe_name . '.' . $extension;
                    $counter = 1;
                    
                    // Check if file exists, add (1), (2), etc. if needed
                    while (file_exists($upload_dir . $filename)) {
                        $filename = $safe_name . '_(' . $counter . ').' . $extension;
                        $counter++;
                    }
                    
                    $location = $upload_dir . $filename;
                    move_uploaded_file($_FILES["image"]["tmp_name"], $location);
                    $image = 'client-dashboard/blog/uploads/posts/' . $filename;
                }
            }

            if (!isset($error_message)) {
                // Debug: About to insert post
                error_log('FORM SUBMITTED - About to insert post with title: ' . $title);
                error_log('FORM SUBMITTED - Author: ' . $author . ', Author ID: ' . $author_id);
                error_log('FORM SUBMITTED - Content length: ' . strlen($content));
                try {
                    $stmt = $blog_pdo->prepare("INSERT INTO `posts` (category_id, title, slug, author_id, image, content, date, time, active, featured)
                                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $result = $stmt->execute([$category_id, $title, $slug, $author_id, $image, $content, $date, $time, $active, $featured]);
                    error_log('FORM SUBMITTED - Database insert result: ' . ($result ? 'SUCCESS' : 'FAILED'));

                    $post_id = $blog_pdo->lastInsertId();
                    // Debug: Post inserted successfully
                    error_log('FORM SUBMITTED - Post inserted successfully with ID: ' . $post_id);

                    // Send newsletter emails via Graph API
                    $from     = $settings['email'];
                    $sitename = $settings['sitename'];

                    // Debug: Log original content before conversion
                    error_log('EMAIL DEBUG - Original content length: ' . strlen($content));
                    error_log('EMAIL DEBUG - site_url setting: ' . $settings['site_url']);
                    error_log('EMAIL DEBUG - First 500 chars of content: ' . substr($content, 0, 500));
                    
                    // Convert relative image paths to absolute URLs in content
                    $email_content = preg_replace_callback(
                        '/<img([^>]*)src=["\'](?!http)([^"\']+)["\']([^>]*)>/i',
                        function($matches) use ($settings) {
                            $before = $matches[1];
                            $src = $matches[2];
                            $after = $matches[3];
                            
                            error_log('EMAIL DEBUG - Found RELATIVE image src: ' . $src);
                            
                            // Remove leading slashes
                            $src = ltrim($src, '/');
                            
                            // Check if src already starts with the blog path to avoid duplication
                            $blog_path = 'client-dashboard/blog/';
                            if (strpos($src, $blog_path) === 0) {
                                // Image path already has blog path, use main domain
                                $domain = parse_url($settings['site_url'], PHP_URL_SCHEME) . '://' . parse_url($settings['site_url'], PHP_URL_HOST);
                                $full_url = rtrim($domain, '/') . '/' . $src;
                            } else {
                                // Image path doesn't have blog path, append to site_url
                                $full_url = rtrim($settings['site_url'], '/') . '/' . $src;
                            }
                            
                            error_log('EMAIL DEBUG - Converted to: ' . $full_url);
                            
                            return '<img' . $before . 'src="' . $full_url . '"' . $after . '>';
                        },
                        $content
                    );
                    
                    // Also convert localhost URLs to production URLs for emails
                    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                        $email_content = preg_replace_callback(
                            '/<img([^>]*)src=["\']http:\/\/localhost:3000\/public_html\/([^"\']+)["\']([^>]*)>/i',
                            function($matches) {
                                $before = $matches[1];
                                $path = $matches[2];
                                $after = $matches[3];
                                
                                error_log('EMAIL DEBUG - Found LOCALHOST image: ' . $path);
                                
                                $production_url = 'https://glitchwizarddigitalsolutions.com/' . $path;
                                
                                error_log('EMAIL DEBUG - Converted localhost to: ' . $production_url);
                                
                                return '<img' . $before . 'src="' . $production_url . '"' . $after . '>';
                            },
                            $email_content
                        );
                    }
                    
                    error_log('EMAIL DEBUG - Converted content length: ' . strlen($email_content));
                    error_log('EMAIL DEBUG - Number of images found: ' . substr_count(strtolower($email_content), '<img'));

                    // Prepare featured image for email
                    $featured_image_html = '';
                    if (!empty($image)) {
                        $image_url = $image;
                        // Convert localhost to production if in development
                        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                            $image_url = str_replace('http://localhost:3000/public_html/', 'https://glitchwizarddigitalsolutions.com/', $image_url);
                        }
                        // Ensure it's an absolute URL
                        if (strpos($image_url, 'http') !== 0) {
                            $image_url = 'https://glitchwizarddigitalsolutions.com/' . ltrim($image_url, '/');
                        }
                        $featured_image_html = '<p style="text-align: center;"><img src="' . $image_url . '" alt="' . htmlspecialchars($title) . '" style="max-width: 100%; height: auto; border-radius: 8px;"></p>';
                        error_log('EMAIL DEBUG - Featured image URL: ' . $image_url);
                    }

                    $stmt = $blog_pdo->query("SELECT * FROM `newsletter`");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $to = $row['email'];
                        $subject = $title;
                        $message = '
<html>
<body>
  <b><h1>' . $settings['sitename'] . '</h1><b/>
  <h2>New post: <b><a href="' . rtrim($settings['site_url'], '/') . '/post.php?id=' . $post_id . '" title="Read more">' . $title . '</a></b></h2><br />

  ' . $featured_image_html . '

  ' . html_entity_decode($email_content) . '

  <hr />
  <i>If you do not want to receive more notifications, you can <a href="' . rtrim($settings['site_url'], '/') . '/unsubscribe?email=' . $to . '">Unsubscribe</a></i>
</body>
</html>
';

                        // Send via Graph API with general context (webmaster@ reply-to)
                        send_contextual_email('general', $to, explode('@', $to)[0], $subject, $message);
                    }

                    $success_message = 'Post created successfully! <a href="' . $settings['site_url'] . '/post.php?id=' . $post_id . '" target="_blank" class="btn btn-sm btn-success">View Post</a> <a href="posts.php" class="btn btn-sm btn-primary">Back to Posts</a>';

                } catch (PDOException $e) {
                    // Debug: Database error
                    error_log('Database error: ' . $e->getMessage());
                    $error_message = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }

// Debug: Script reached template rendering
error_log('FORM PROCESSING COMPLETE - Success: ' . (isset($success_message) ? 'yes' : 'no') . ', Error: ' . (isset($error_message) ? 'yes' : 'no'));
if (isset($success_message)) {
    error_log('FORM PROCESSING COMPLETE - Success message: ' . $success_message);
}
if (isset($error_message)) {
    error_log('FORM PROCESSING COMPLETE - Error message: ' . $error_message);
}

// Use the admin template system
?>
<?=template_admin_header('Add Post', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Posts', 'url' => 'posts.php'],
    ['title' => 'Add Post', 'url' => '']
])?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-edit"></i>
        <div class="txt">
            <h2>Add Post</h2>
            <p>Create a new blog post</p>
        </div>
    </div>
</div>

<?php if (isset($success_message)): ?>
<div class="msg success">
    <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>
    <?php echo $success_message; ?>
</div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
<div class="msg error">
    <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9L289 241l47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"/></svg>
    <?php echo $error_message; ?>
</div>
<?php endif; ?>

<div class="form-professional">
    <div class="card">
        <h6 class="card-header">Add Post</h6>
        <div class="card-body">
            <form name="post_form" action="" method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input class="form-control" name="title" id="title" value="" type="text" oninput="countText()" maxlength="250" required>
                            <div class="form-text">
                                <i>For best SEO keep title under 50 characters.</i>
                                <label for="characters" class="ms-2">Characters: </label>
                                <span id="characters">0</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Content</label>
                            <textarea class="form-control" id="content" rows="12" name="content"></textarea>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="image" class="form-label">Featured Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>

                        <div class="mb-3">
                            <label for="active" class="form-label">Active</label>
                            <select name="active" class="form-select" required>
                                <option value="Yes" selected>Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="featured" class="form-label">Featured</label>
                            <select name="featured" class="form-select" required>
                                <option value="Yes">Yes</option>
                                <option value="No" selected>No</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select name="category_id" class="form-select" required>
                                <?php
                                try {
                                    $stmt = $blog_pdo->query("SELECT * FROM `categories` ORDER BY category ASC");
                                    while ($rw = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . $rw['id'] . '">' . htmlspecialchars($rw['category']) . '</option>';
                                    }
                                } catch (Exception $e) {
                                    echo '<option value="">Error loading categories</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <input type="submit" name="add" class="btn btn-primary" value="Add Post" />
                </div>
            </form>
        </div>
    </div>
</div>

<!-- TinyMCE -->
<script src="tinymce/tinymce/js/tinymce/tinymce.min.js"></script>

<script>
// Character counter function
function countText() {
    const titleInput = document.getElementById('title');
    const charactersSpan = document.getElementById('characters');
    if (titleInput && charactersSpan) {
        charactersSpan.textContent = titleInput.value.length;
    }
}

// Initialize TinyMCE after page load
window.addEventListener('load', function() {
    tinymce.init({
    selector: "#content",
    plugins: "image table lists media link code",
    toolbar: "undo redo | insert_template | blocks | formatselect | bold italic forecolor | align | outdent indent | numlist bullist | table image link | code",
    menubar: "edit view insert format tools table",
    valid_elements: "*[*]",
    extended_valid_elements: "*[*]",
    valid_children: "+body[style]",
    content_css: false,
    height: 400,
    branding: false,
    promotion: false,
    automatic_uploads: true,
    images_upload_url: "tinymce_upload.php",
    images_upload_handler: function (blobInfo, progress) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "tinymce_upload.php", true);

            const formData = new FormData();
            formData.append("file", blobInfo.blob(), blobInfo.filename());

            xhr.upload.onprogress = (e) => {
                progress(e.loaded / e.total * 100);
            };

            xhr.onload = () => {
                if (xhr.status === 200) {
                    try {
                        const json = JSON.parse(xhr.responseText);
                        if (json.error) {
                            reject(json.error);
                        } else {
                            resolve(json.location);
                        }
                    } catch (err) {
                        reject("Invalid JSON response from server");
                    }
                } else {
                    reject("HTTP Error: " + xhr.status);
                }
            };

            xhr.onerror = () => {
                reject("Image upload failed");
            };

            xhr.send(formData);
        });
    },
    file_picker_callback: function(callback, value, meta) {
        if (meta.filetype === "image") {
            if (confirm('Click OK to upload a new image, or Cancel to browse existing images')) {
                const input = document.createElement("input");
                input.setAttribute("type", "file");
                input.setAttribute("accept", "image/*");

                input.onchange = function() {
                    const file = this.files[0];
                    if (file) {
                        const formData = new FormData();
                        formData.append("file", file);

                        fetch("tinymce_upload.php", {
                            method: "POST",
                            body: formData
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('HTTP error! status: ' + response.status);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.error) {
                                alert("Upload error: " + data.error);
                            } else {
                                callback(data.location, {
                                    alt: file.name.replace(/\.[^/.]+$/, ""),
                                    class: "responsive-image"
                                });
                            }
                        })
                        .catch(error => {
                            console.error("Upload failed:", error);
                            alert("Upload failed: " + error.message);
                        });
                    }
                };

                input.click();
            } else {
                fetch('tinymce_upload.php?list_images=1')
                    .then(response => response.json())
                    .then(images => {
                        if (images.length === 0) {
                            alert('No images uploaded yet. Please upload an image first.');
                            const input = document.createElement("input");
                            input.setAttribute("type", "file");
                            input.setAttribute("accept", "image/*");
                            input.onchange = function() {
                                const file = this.files[0];
                                if (file) {
                                    const formData = new FormData();
                                    formData.append("file", file);
                                    fetch("tinymce_upload.php", {
                                        method: "POST",
                                        body: formData
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.error) {
                                            alert("Upload error: " + data.error);
                                        } else {
                                            callback(data.location, {
                                                alt: file.name.replace(/\.[^/.]+$/, ""),
                                                class: "responsive-image"
                                            });
                                        }
                                    })
                                    .catch(error => {
                                        alert("Upload failed: " + error);
                                    });
                                }
                            };
                            input.click();
                            return;
                        }

                        let html = '<div style="padding: 20px; max-height: 400px; overflow-y: auto;">';
                        html += '<h3 style="margin-top: 0;">Select an Image</h3>';
                        html += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;">';

                        images.forEach((img) => {
                            html += `
                                <div style="border: 2px solid #ddd; border-radius: 8px; padding: 10px; cursor: pointer; text-align: center;"
                                     onclick="selectImage('${img.value}', '${img.title}')"
                                     onmouseover="this.style.borderColor='#6b46c1'"
                                     onmouseout="this.style.borderColor='#ddd'">
                                    <img src="${img.value}" style="width: 100%; height: 100px; object-fit: cover; border-radius: 4px;">
                                    <div style="margin-top: 5px; font-size: 11px; color: #666;">${img.title}</div>
                                </div>
                            `;
                        });

                        html += '</div></div>';

                        const modal = document.createElement('div');
                        modal.id = 'image-browser-modal';
                        modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 10000; display: flex; align-items: center; justify-content: center;';

                        const content = document.createElement('div');
                        content.style.cssText = 'background: white; border-radius: 8px; max-width: 800px; width: 90%; max-height: 80vh; overflow: hidden;';
                        content.innerHTML = html;

                        modal.appendChild(content);
                        document.body.appendChild(modal);

                        window.selectImage = function(src, alt) {
                            callback(src, {
                                alt: alt.replace(/\.[^/.]+$/, ''),
                                class: 'responsive-image'
                            });
                            closeImageBrowser();
                        };

                        window.closeImageBrowser = function() {
                            document.getElementById('image-browser-modal').remove();
                        };
                    });
            }
        }
    },
    link_default_protocol: "https",
    link_assume_external_targets: false,
    allow_unsafe_link_target: true,
    convert_urls: false,
    relative_urls: false,
    remove_script_host: false,
    image_title: true,
    image_description: true,
    license_key: "gpl",
    setup: function (editor) {
        editor.ui.registry.addMenuButton("insert_template", {
            icon: "template",
            tooltip: "Use Existing Template",
            fetch: function (callback) {
                fetch("get_blog_template.php?list=1")
                    .then(response => response.json())
                    .then(templates => {
                        const items = templates.map(function(template) {
                            return {
                                type: "menuitem",
                                text: template.title,
                                onAction: function () {
                                    fetch("get_blog_template.php?id=" + template.id)
                                        .then(response => response.json())
                                        .then(data => {
                                            editor.setContent(data.content);
                                        });
                                }
                            };
                        });
                        callback(items);
                    });
            }
        });
    }
    });
});

// Initialize character counter on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded fired');
    countText(); // Set initial count

    // Reset button state if there are error messages (page reloaded with validation errors)
    const errorAlert = document.querySelector('.msg.error');
    if (errorAlert) {
        const submitBtn = document.querySelector('input[type="submit"]');
        if (submitBtn) {
            submitBtn.value = 'Add Post';
            submitBtn.disabled = false;
        }
    }

    const form = document.querySelector('form[name="post_form"]');
    console.log('Form found:', form ? 'YES' : 'NO');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('Form submit event triggered');
            
            // Ensure TinyMCE content is saved to textarea before form submission
            if (typeof tinymce !== 'undefined') {
                console.log('TinyMCE is defined');
                const editor = tinymce.get('content');
                if (editor) {
                    console.log('TinyMCE editor found, saving content');
                    editor.save();
                } else {
                    console.log('TinyMCE editor NOT found for #content');
                }
            } else {
                console.log('TinyMCE is NOT defined');
            }

            // Check if content is empty
            const contentTextarea = document.getElementById('content');
            console.log('Content textarea found:', contentTextarea ? 'YES' : 'NO');
            console.log('Content value:', contentTextarea ? contentTextarea.value.substring(0, 50) : 'N/A');
            console.log('Content length:', contentTextarea ? contentTextarea.value.length : 0);
            
            if (contentTextarea && contentTextarea.value.trim().length === 0) {
                console.log('Content is empty, preventing submission');
                e.preventDefault();
                alert('Please enter some content for the post.');
                return false;
            }

            console.log('Validation passed, form will submit');
            
            // DON'T disable the button - it prevents the button name from being sent in POST!
            // Instead, just change the text to show it's processing
            const submitBtn = form.querySelector('input[type="submit"]');
            if (submitBtn) {
                submitBtn.value = 'Saving...';
                // Remove: submitBtn.disabled = true;
            }
        });
    } else {
        console.log('ERROR: Form not found!');
    }
});
</script>

<?=template_admin_footer()?>
