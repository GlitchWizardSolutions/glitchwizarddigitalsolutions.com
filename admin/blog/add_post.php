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
    
	$slug = generateSeoURL($title);
    $active = $_POST['active'];
	$featured = $_POST['featured'];
    $category_id = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;
    $content = $_POST['content'];
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
            echo '<div class="alert alert-danger">The file is not an image.</div>';
            $uploadOk = 0;
        }
        
        // Check file size
        if ($_FILES["image"]["size"] > 10000000) {
            echo '<div class="alert alert-warning">Sorry, your file is too large.</div>';
            $uploadOk = 0;
        }
        
        if ($uploadOk == 1) {
            $string     = "0123456789wsderfgtyhjuk";
            $new_string = str_shuffle($string);
            $location   = "../../client-dashboard/blog/uploads/posts/image_$new_string.$imageFileType";
            move_uploaded_file($_FILES["image"]["tmp_name"], $location);
            $image = 'client-dashboard/blog/uploads/posts/image_' . $new_string . '.' . $imageFileType . '';
	   }
    }
    
    $stmt = $blog_pdo->prepare("INSERT INTO `posts` (category_id, title, slug, author_id, image, content, date, time, active, featured) 
								   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
	$stmt->execute([$category_id, $title, $slug, $author_id, $image, $content, $date, $time, $active, $featured]);
    
    $post_id = $blog_pdo->lastInsertId();
    $from     = $settings['email'];
    $sitename = $settings['sitename'];
	
    $stmt = $blog_pdo->query("SELECT * FROM `newsletter`");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $to = $row['email'];
        $subject = $title;
        $message = '
<html>
<body>
  <b><h1>' . $settings['sitename'] . '</h1><b/>
  <h2>New post: <b><a href="' . $settings['site_url'] . '/post.php?id=' . $post_id . '" title="Read more">' . $title . '</a></b></h2><br />

  ' . html_entity_decode($content) . '
  
  <hr />
  <i>If you do not want to receive more notifications, you can <a href="' . $settings['site_url'] . '/unsubscribe?email=' . $to . '">Unsubscribe</a></i>
</body>
</html>
';
        
        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

        $headers .= 'From: ' . $from . '';
        
        @mail($to, $subject, $message, $headers);
    }
    
    header('Location: posts.php');
    exit;
}
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

<div class="form-professional">
    <div class="card">
        <h6 class="card-header">Add Post</h6>         
            <div class="card-body">
                <form name="post_form" action="" method="post" enctype="multipart/form-data">
					<p>
						<label>Title</label>
						<input class="form-control" name="title" id="title" value="" type="text" oninput="countText()" maxlength="250" required>
						<i>For best SEO keep title under 50 characters.</i>
						<label for="characters">Characters: </label>
						<span id="characters">0</span><br>
					</p>
					<p>
						<label>Image</label>
						<input type="file" name="image" class="form-control" />
					</p>
					<p>
						<label>Active</label><br />
						<select name="active" class="form-select" required>
							<option value="Yes" selected>Yes</option>
							<option value="No">No</option>
                        </select>
					</p>
					<p>
						<label>Featured</label><br />
						<select name="featured" class="form-select" required>
							<option value="Yes">Yes</option>
							<option value="No" selected>No</option>
                        </select>
					</p>
					<p>
						<label>Category</label><br />
						<select name="category_id" class="form-select" required>
<?php
$stmt = $blog_pdo->query("SELECT * FROM `categories`");
while ($rw = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo '
                            <option value="' . $rw['id'] . '">' . htmlspecialchars($rw['category']) . '</option>
									';
}
?>
						</select>
					</p>
					<p>
						<label>Content</label>
						<textarea class="form-control" id="content" rows="8" name="content" required></textarea>
					</p>
								
					<input type="submit" name="add" class="btn btn-primary col-12" value="Add" />
				</form>                      
            </div>
    </div>
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
                        html += '<p style="color: #666; font-size: 13px; margin-bottom: 15px;">Images will automatically be responsive in blog posts</p>';
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
    const form = document.querySelector('form[name="post_form"]');
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
                alert('Please enter some content for the post.');
                return false;
            }
        });
    }
});
</script>