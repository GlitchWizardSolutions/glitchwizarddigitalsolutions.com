<?php
require 'assets/includes/admin_config.php';

// User authentication for blog admin
if (isset($_SESSION['sec-username'])) {
    $uname = $_SESSION['sec-username'];
    $stmt = $blog_pdo->prepare("SELECT * FROM `users` WHERE username = ? AND (role = 'Admin' OR role = 'Editor')");
    $stmt->execute([$uname]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        header('Location: ../../blog/login');
        exit;
    }
} else {
    header('Location: ../../blog/login');
    exit;
}

if (isset($_POST['add'])) {
    $title = trim($_POST['title']);
    $content = $_POST['content'];
    $category_id = (int) $_POST['category_id'];
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
            <input type="text" name="title" class="form-control" placeholder="Template title..." required>
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
            <textarea class="form-control" id="content" rows="12" name="content" required></textarea>
        </p>

        <input type="submit" name="add" class="btn btn-primary col-12" value="Create Template" />
    </form>
</div>

<?=template_admin_footer('
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.3.0/tinymce.min.js" integrity="sha512-RUZ2d69UiTI+LdjfDCxqJh5HfjmOcouct56utQNVRjr90Ea8uHQa+gCxvxDTC9fFvIGP+t4TDDJWNTRV48tBpQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
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
            xhr.open("POST", "ajax_upload.php", true);

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
                            throw new Error(\'HTTP error! status: \' + response.status);
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
</script>

<script>
// Ensure form validation works with TinyMCE
document.addEventListener(\'DOMContentLoaded\', function() {
    const form = document.querySelector(\'form\');
    if (form) {
        form.addEventListener(\'submit\', function(e) {
            // Ensure TinyMCE content is saved to textarea before form submission
            if (typeof tinymce !== \'undefined\') {
                tinymce.triggerSave();
            }
        });
    }
});
</script>
')?>