<?php
require 'assets/includes/admin_config.php';

// Check if the ID param exists
if (isset($_GET['id'])) {
    // Retrieve the blog template from the database
    $stmt = $blog_pdo->prepare('SELECT * FROM blog_templates WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    // ID param exists, edit an existing template
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Update the template
        $title = trim($_POST['title']);
        $content = $_POST['content'];
        $category_id = (int) $_POST['category_id'];
        $active = $_POST['active'];

        $stmt = $blog_pdo->prepare('UPDATE blog_templates SET title = ?, content = ?, category_id = ?, active = ? WHERE id = ?');
        $stmt->execute([$title, $content, $category_id, $active, $_GET['id']]);

        header('Location: blog_templates.php?success_msg=2');
        exit;
    }
} else {
    header('Location: blog_templates.php');
    exit;
}
?>
<?=template_admin_header('Edit Blog Template', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Templates', 'url' => 'blog_templates.php'],
    ['title' => 'Edit Template', 'url' => '']
])?>

<div class="content-block">
    <h3>Edit Blog Template</h3>
    <form action="" method="post">
        <p>
            <label>Title</label><br />
            <input type="text" name="title" class="form-control" value="<?=htmlspecialchars($template['title'], ENT_QUOTES)?>" required>
        </p>
        <p>
            <label>Category (Optional)</label><br />
            <select name="category_id" class="form-select">
                <option value="">No Category</option>
<?php
$stmt = $blog_pdo->query("SELECT * FROM `categories` ORDER BY category ASC");
while ($rw = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $selected = ($rw['id'] == $template['category_id']) ? 'selected' : '';
    echo '
                <option value="' . $rw['id'] . '" ' . $selected . '>' . htmlspecialchars($rw['category']) . '</option>
                ';
}
?>
            </select>
        </p>
        <p>
            <label>Status</label><br />
            <select name="active" class="form-select" required>
                <option value="Yes" <?=$template['active'] == 'Yes' ? 'selected' : ''?>>Active</option>
                <option value="No" <?=$template['active'] == 'No' ? 'selected' : ''?>>Inactive</option>
            </select>
        </p>
        <p>
            <label>Content</label>
            <textarea class="form-control" id="content" rows="12" name="content" required><?=$template['content']?></textarea>
        </p>

        <input type="submit" name="submit" class="btn btn-primary col-12" value="Update Template" />
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