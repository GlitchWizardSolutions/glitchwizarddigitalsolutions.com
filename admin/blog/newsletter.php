<?php
require 'assets/includes/admin_config.php';

if (isset($_GET['unsubscribe'])) {
	$unsubscribe_email = $_GET['unsubscribe'];

    $stmt = $blog_pdo->prepare("SELECT * FROM `newsletter` WHERE email = ? LIMIT 1");
    $stmt->execute([$unsubscribe_email]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        $stmt = $blog_pdo->prepare("DELETE FROM `newsletter` WHERE email = ?");
        $stmt->execute([$unsubscribe_email]);
    }
}
?>
<?=template_admin_header('Newsletter', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Newsletter', 'url' => '']
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-envelope"></i>
        <div class="txt">
            <h2>Newsletter</h2>
            <p>Manage newsletter subscribers and send messages</p>
        </div>
    </div>
</div>

<div class="form-professional">
        <div class="card">
			<h6 class="card-header">Send mass message</h6>         
			<div class="card-body">
<?php
if (isset($_POST['send_mass_message'])) {
    $title = trim($_POST['title']);
    $content = $_POST['content'];

    $from = $settings['email'];
    $sitename = $settings['sitename'];
    
    // Debug: Log before conversion
    error_log('NEWSLETTER DEBUG - Original content length: ' . strlen($content));
    error_log('NEWSLETTER DEBUG - site_url: ' . $settings['site_url']);
    error_log('NEWSLETTER DEBUG - Images in original: ' . substr_count(strtolower($content), '<img'));
    
    // Convert relative image paths to absolute URLs
    $content = preg_replace_callback(
        '/<img([^>]*)src=["\'](?!http)([^"\']+)["\']([^>]*)>/i',
        function($matches) use ($settings) {
            $before = $matches[1];
            $src = $matches[2];
            $after = $matches[3];
            
            error_log('NEWSLETTER DEBUG - Found RELATIVE image src: ' . $src);
            
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
            
            error_log('NEWSLETTER DEBUG - Converted to: ' . $full_url);
            
            return '<img' . $before . 'src="' . $full_url . '"' . $after . '>';
        },
        $content
    );
    
    // Also convert localhost URLs to production URLs for emails
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        $content = preg_replace_callback(
            '/<img([^>]*)src=["\']http:\/\/localhost:3000\/public_html\/([^"\']+)["\']([^>]*)>/i',
            function($matches) {
                $before = $matches[1];
                $path = $matches[2];
                $after = $matches[3];
                
                error_log('NEWSLETTER DEBUG - Found LOCALHOST image: ' . $path);
                
                $production_url = 'https://glitchwizarddigitalsolutions.com/' . $path;
                
                error_log('NEWSLETTER DEBUG - Converted localhost to: ' . $production_url);
                
                return '<img' . $before . 'src="' . $production_url . '"' . $after . '>';
            },
            $content
        );
    }
    
    error_log('NEWSLETTER DEBUG - After conversion length: ' . strlen($content));
    error_log('NEWSLETTER DEBUG - Images after conversion: ' . substr_count(strtolower($content), '<img'));
	
    $stmt = $blog_pdo->query("SELECT * FROM `newsletter`");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		
        $to = $row['email'];
		
        $subject = $title;
        
        $message = '
<html>
<body>
  <b><h1><a href="' . rtrim($settings['site_url'], '/') . '/" title="Visit the website">' . $settings['sitename'] . '</a></h1><b/>
  <br />

  ' . html_entity_decode($content) . '
  
  <hr />
  <i>If you do not want to receive more notifications, you can <a href="' . rtrim($settings['site_url'], '/') . '/unsubscribe?email=' . $to . '">Unsubscribe</a></i>
</body>
</html>
';
        
        // Send via Microsoft Graph API instead of PHP mail()
        send_contextual_email('general', $to, explode('@', $to)[0], $subject, $message);
    }
    
    echo '<div class="alert alert-success">' . svg_icon_email() . ' Your global message has been sent successfully.</div>';
}
?>
				<form action="" method="post">
					<p>
						<label>Title</label>
						<input class="form-control" name="title" value="" type="text" required>
					</p>
					<p>
						<label>Content</label>
						<textarea class="form-control" id="content" name="content"></textarea>
					</p>
								
					<input type="submit" name="send_mass_message" class="btn btn-primary col-12" value="Send" />
				</form>
			</div>
        </div>
</div><br />
			
			<div class="card">
              <h6 class="card-header">Subscribers</h6>         
                  <div class="card-body">
                    <table class="table table-border table-hover" width="100%">
                          <thead>
                              <tr>
                                  <th>E-Mail</th>
								  <th>Actions</th>
                              </tr>
                          </thead>
                          <tbody>
<?php
$stmt = $blog_pdo->query("SELECT * FROM newsletter ORDER BY email ASC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo '
                            <tr>
                                <td>' . htmlspecialchars($row['email']) . '</td>
								<td>
									<a href="?unsubscribe=' . urlencode($row['email']) . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to unsubscribe this email?\')"><i class="fas fa-bell-slash"></i> Unsubscribe</a>
								</td>
                            </tr>
';
    }
?>
                          </tbody>
                     </table>
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
                alert('Please enter some content for the newsletter.');
                return false;
            }
        });
    }
});
</script>

<?=template_admin_footer()?>