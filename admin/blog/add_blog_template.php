<?php
require 'assets/includes/admin_config.php';

// Get username from main admin session
$uname = $_SESSION['name'] ?? '';
if (empty($uname)) {
    // Fallback: try to get from account data if session is not set
    $uname = $account['username'] ?? '';
}

if (isset($_POST['add'])) {
    $title = trim($_POST['title']);
    
    // Validate title length
    if (strlen($title) > 250) {
        $title = substr($title, 0, 250);
    }
    
    $content = $_POST['content'];
    $category_id = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;
    $active = $_POST['active'];

    $created_date = date('Y-m-d');
    $created_time = date('H:i:s');

    // Get current user ID from blog users table
    $stmt = $blog_pdo->prepare("SELECT id FROM `users` WHERE username = ? LIMIT 1");
    $stmt->execute([$uname]);
    $auth = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$auth) {
        // User doesn't exist in blog database, create them
        $stmt = $blog_pdo->prepare("INSERT INTO users (username, role) VALUES (?, 'Admin')");
        $stmt->execute([$uname]);
        $created_by = $blog_pdo->lastInsertId();
    } else {
        $created_by = $auth['id'];
    }

    $stmt = $blog_pdo->prepare("INSERT INTO `blog_templates` (title, content, category_id, created_by, created_date, created_time, active)
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $content, $category_id, $created_by, $created_date, $created_time, $active]);

    header('Location: blog_templates.php?success_msg=1');
    exit;
}
?>
<?=template_admin_header('Add Blog Template', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Templates', 'url' => 'blog_templates.php'],
    ['title' => 'Add Template', 'url' => '']
])?>

<div class="content-block">
    <h3>Add Blog Template</h3>
    <form action="" method="post">
        <p>
            <label>Title</label><br />
            <input type="text" name="title" class="form-control" placeholder="Template title..." maxlength="250" required>
            <small class="text-muted">Maximum 250 characters</small>
        </p>
        <p>
            <label>Category (Optional)</label><br />
            <select name="category_id" class="form-select">
                <option value="">No Category</option>
<?php
$stmt = $blog_pdo->query("SELECT * FROM `categories` ORDER BY category ASC");
while ($rw = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo '
                <option value="' . $rw['id'] . '">' . htmlspecialchars($rw['category']) . '</option>
                ';
}
?>
            </select>
        </p>
        <p>
            <label>Status</label><br />
            <select name="active" class="form-select" required>
                <option value="Yes">Active</option>
                <option value="No">Inactive</option>
            </select>
        </p>
        <p>
            <label>Content</label>
            <textarea class="form-control" id="content" rows="12" name="content"></textarea>
        </p>

        <input type="submit" name="add" class="btn btn-primary col-12" value="Create Template" />
    </form>
</div>

<script src="tinymce/tinymce/js/tinymce/tinymce.min.js"></script>
<script>
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
                                // Properly set the image source and alt text
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
                            // Re-trigger the file picker for upload
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
                        html += '<p style="color: #666; font-size: 13px; margin-bottom: 15px;">Images will automatically be responsive in blog templates</p>';
                        html += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;">';

                        images.forEach((img) => {
                            html += `
                                <div style="border: 2px solid #ddd; border-radius: 8px; padding: 10px; cursor: pointer; text-align: center;" 
                                     onclick="selectImage('${img.value}', '${img.title}')" 
                                     onmouseover="this.style.borderColor='#6b46c1'" 
                                     onmouseout="this.style.borderColor='#ddd'">
                                    <img src="${img.value}" style="width: 100%; height: 100px; object-fit: cover; border-radius: 4px;">
                                    <div style="margin-top: 5px; font-size: 11px; color: #666; overflow: hidden; text-overflow: ellipsis;">${img.title}</div>
                                </div>
                            `;
                        });

                        html += '</div>';
                        html += '<div style="margin-top: 20px; text-align: center;">';
                        html += '<button onclick="closeImageBrowser()" style="padding: 8px 20px; background: #6b46c1; color: white; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>';
                        html += '</div>';
                        html += '</div>';

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
</script>

<?=template_admin_footer()?>

<script>
// Ensure form validation works with TinyMCE
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Ensure TinyMCE content is saved to textarea before form submission
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }
            
            // Check if content is empty
            const contentTextarea = document.getElementById('content');
            if (!contentTextarea || !contentTextarea.value.trim()) {
                e.preventDefault();
                alert('Please enter some content for the template.');
                return false;
            }
        });
    }
});
</script>