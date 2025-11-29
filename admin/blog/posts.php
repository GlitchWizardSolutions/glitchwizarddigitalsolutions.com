<?php
//2025-06-24 Production
error_log('Loading Page: /admin/blog/posts ');
require 'assets/includes/admin_config.php';
require_once __DIR__ . '/functions.php';

if (isset($_GET['delete-id'])) {
    $id = (int) $_GET["delete-id"];

    // Get post content before deletion to check for images
    $stmt = $blog_pdo->prepare("SELECT content, image FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($post) {
        // Delete the post
        $stmt = $blog_pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$id]);

        // Delete related comments
        $stmt = $blog_pdo->prepare("DELETE FROM comments WHERE post_id = ?");
        $stmt->execute([$id]);

        // Clean up unused images
        cleanup_unused_images($post['content'], $post['image']);
    }

    header('Location: posts.php');
    exit;
}

if (isset($_GET['edit-id'])) {
    $id = (int) $_GET["edit-id"];
    $stmt = $blog_pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
	
    if (empty($id) || !$row) {
        header('Location: posts.php');
        exit;
    }
	
	if (isset($_POST['submit'])) {
        $title = trim($_POST['title']);
        
        // Validate title length
        if (strlen($title) > 250) {
            $title = substr($title, 0, 250);
        }
        
		$slug = generateSeoURL($title);
        $image = $row['image'];
        $active = $_POST['active'];
		$featured = $_POST['featured'];
        $category_id = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;
        $content = htmlspecialchars($_POST['content']);
		
		$date = date($settings['date_format']);
		$time = date('H:i');
        
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
                $string = "0123456789wsderfgtyhjuk";
                $new_string = str_shuffle($string);
                $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
                $location = "../../client-dashboard/blog/uploads/posts/image_$new_string.$imageFileType";
                move_uploaded_file($_FILES["image"]["tmp_name"], $location);
                $image = 'client-dashboard/blog/uploads/posts/image_' . $new_string . '.' . $imageFileType;
            }
        }
        
        $stmt = $blog_pdo->prepare("UPDATE posts SET title = ?, slug = ?, image = ?, active = ?, featured = ?, date = ?, time = ?, category_id = ?, content = ? WHERE id = ?");
        $stmt->execute([$title, $slug, $image, $active, $featured, $date, $time, $category_id, $content, $id]);
        header('Location: posts.php');
        exit;
    }
}
?>
<?=template_admin_header('Blog Posts', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Posts', 'url' => '']
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-edit"></i>
        <div class="txt">
            <h2>Blog Posts</h2>
            <p>Manage blog posts</p>
        </div>
    </div>
</div>

<?php if (isset($_GET['edit-id'])): ?>
<div class="form-professional">
	<div class="content-block">
		<h3>Edit Post</h3>         
			<form name="post_form" action="" method="post" enctype="multipart/form-data" style="max-width: 900px;">
				<p>
					<label>Title</label>
					<input name="title" id="title" type="text" value="<?= htmlspecialchars($row['title']) ?>" style="width: 100%;" oninput="countText()" maxlength="250" required>
					<i>For best SEO keep title under 50 characters.</i>
					<label for="characters">Characters: </label>
					<span id="characters"><?= strlen($row['title']) ?></span><br>
				</p>
				<p>
					<label>Image</label><br />
<?php if ($row['image'] != ''): ?>
					<img src="../../<?= htmlspecialchars($row['image']) ?>" width="100px" height="100px" style="object-fit: cover; border-radius: 5px;" /><br /><br />
<?php endif; ?>
					<input type="file" name="image" />
				</p>
				<p>
				<label>Active</label><br />
				<select name="active" style="width: 100%;" required>
					<option value="Yes" <?= $row['active'] == "Yes" ? 'selected' : '' ?>>Yes</option>
					<option value="No" <?= $row['active'] == "No" ? 'selected' : '' ?>>No</option>
				</select>
				</p>
				<p>
					<label>Featured</label><br />
					<select name="featured" style="width: 100%;" required>
						<option value="Yes" <?= $row['featured'] == "Yes" ? 'selected' : '' ?>>Yes</option>
						<option value="No" <?= $row['featured'] == "No" ? 'selected' : '' ?>>No</option>
					</select>
				</p>
				<p>
					<label>Category</label><br />
					<select name="category_id" style="width: 100%;" required>
<?php
$stmt = $blog_pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($categories as $cat) {
	$selected = ($row['category_id'] == $cat['id']) ? 'selected' : '';
	echo '<option value="' . $cat['id'] . '" ' . $selected . '>' . htmlspecialchars($cat['category']) . '</option>';
}
?>
					</select>
				</p>
				<p>
					<label>Content</label>
					<textarea name="content" id="content" rows="8" style="width: 100%;" required><?= html_entity_decode($row['content']) ?></textarea>
				</p>

				<input type="submit" class="btn" name="submit" value="Save" />
			</form>
	</div>
</div>
<?php endif; ?>

	<div class="content-block">
		<h3>All Posts</h3>
		<a href="add_post.php" class="btn btn-primary" style="margin-bottom: 1rem;"><i class="fa fa-plus"></i> Add Post</a>
        <div class="table">

			<table width="100%">
				<thead>
					<tr>
						<td>Image</td>
						<td>Title</td>
						<td class="responsive-hidden">Author</td>
						<td class="responsive-hidden">Date</td>
						<td>Active</td>
						<td class="responsive-hidden">Category</td>
						<td class="align-center">Action</td>
					</tr>
				</thead>
				<tbody>
<?php
$stmt = $blog_pdo->query("SELECT * FROM posts ORDER BY id DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$posts) {
    echo '<tr><td colspan="7" class="no-results">No posts found</td></tr>';
}

foreach ($posts as $row) {
    $stmt2 = $blog_pdo->prepare("SELECT category FROM categories WHERE id = ?");
    $stmt2->execute([$row['category_id']]);
    $cat = $stmt2->fetch(PDO::FETCH_ASSOC);
    $category_name = $cat ? htmlspecialchars($cat['category']) : 'Uncategorized';
	
	$featured = '';
	if($row['featured'] == "Yes") {
		$featured = ' <span class="badge green">Featured</span>';
	}
	
	$active_badge = $row['active'] == "Yes" ? '<span class="badge green">Yes</span>' : '<span class="badge red">No</span>';
	
	// Get author name
	$author_name = '-';
	if (!empty($row['author_id'])) {
	    if (function_exists('post_author')) {
	        $author_name = htmlspecialchars(post_author($row['author_id']));
	    } else {
	        // Fallback if function not loaded
	        $stmt3 = $blog_pdo->prepare("SELECT username FROM users WHERE id = ? LIMIT 1");
	        $stmt3->execute([$row['author_id']]);
	        $author = $stmt3->fetch(PDO::FETCH_ASSOC);
	        if ($author) {
	            $author_name = htmlspecialchars($author['username']);
	        }
	    }
	}
	
    echo '
					<tr>
						<td class="img">';
    if ($row['image'] != '') {
        // Normalize image path - remove leading slash and ensure proper path
        $image_path = ltrim($row['image'], '/');
        // If path doesn't include client-dashboard/blog, prepend it
        if (strpos($image_path, 'client-dashboard/blog/') !== 0 && strpos($image_path, 'uploads/') === 0) {
            $image_path = 'client-dashboard/blog/' . $image_path;
        }
        echo '<img src="' . BASE_URL . htmlspecialchars($image_path) . '" width="45px" height="45px" style="object-fit: cover; border-radius: 5px;" />';
    }
    echo '</td>
						<td>' . htmlspecialchars($row['title']) . $featured . '</td>
						<td class="responsive-hidden">' . $author_name . '</td>
						<td class="responsive-hidden" data-sort="' . strtotime($row['date']) . '">' . date($settings['date_format'], strtotime($row['date'])) . '</td>
						<td>' . $active_badge . '</td>
						<td class="responsive-hidden">' . $category_name . '</td>
						<td class="actions">
						    <div class="table-dropdown">
                                <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                                <div class="table-dropdown-items">
                                    <a href="?edit-id=' . $row['id'] . '">
                                        <span class="icon">
                                            <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160V416c0 53 43 96 96 96H352c53 0 96-43 96-96V320c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96z"/></svg>
                                        </span>
                                        Edit
                                    </a>
                                    <a class="red" href="?delete-id=' . $row['id'] . '" onclick="return confirm(\'Are you sure you want to delete this post and all its comments?\');">
                                        <span class="icon">
                                            <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg>
                                        </span>    
                                        Delete
                                    </a>
                                </div>
                            </div>
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

<?=template_admin_footer()?>