const aside = document.querySelector('aside');
if (window.innerWidth < 1000 || localStorage.getItem('admin_menu') == 'minimal') {
    aside.classList.add('minimal');
}
if (window.innerWidth < 1000) {
    document.addEventListener('click', event => {
        if (!aside.classList.contains('minimal') && !event.target.closest('aside') && !event.target.closest('.responsive-toggle') && window.innerWidth < 1000) {
            aside.classList.add('minimal');
        }
    });
}
window.addEventListener('resize', () => {
    if (window.innerWidth < 1000) {
        aside.classList.add('minimal');
    } else if (localStorage.getItem('admin_menu') == 'normal') {
        aside.classList.remove('minimal');
    }
});
document.querySelector('.responsive-toggle').onclick = event => {
    event.preventDefault();
    if (aside.classList.contains('minimal')) {
        aside.classList.remove('minimal');
        localStorage.setItem('admin_menu', 'normal');
    } else {
        aside.classList.add('minimal');
        localStorage.setItem('admin_menu', 'minimal');
    }
};
document.querySelectorAll('.tabs a').forEach((tab_link, tab_link_index) => {
    tab_link.onclick = event => {
        event.preventDefault();
        document.querySelectorAll('.tabs a').forEach(tab_link => tab_link.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach((tab_content, tab_content_index) => {
            if (tab_link_index == tab_content_index) {
                tab_link.classList.add('active');
                tab_content.style.display = 'block';
            } else {
                tab_content.style.display = 'none';
            }
        });
    };
});
if (document.querySelector('.filters a')) {
    let filtersList = document.querySelector('.filters .list');
    let filtersListStyle = window.getComputedStyle(filtersList);
    document.querySelector('.filters a').onclick = event => {
        event.preventDefault();
        if (filtersListStyle.display == 'none') {
            filtersList.style.display = 'flex';
        } else {
            filtersList.style.display = 'none';
        }
    };
    document.addEventListener('click', event => {
        if (!event.target.closest('.filters')) {
            filtersList.style.display = 'none';
        }
    });
}
const initTableDropdown = () => {
    document.querySelectorAll('.table-dropdown').forEach(dropdownElement => {
        dropdownElement.onclick = event => {
            event.preventDefault();
            let dropdownItems = dropdownElement.querySelector('.table-dropdown-items');
            let contextMenu = document.querySelector('.table-dropdown-items-context-menu');
            if (!contextMenu) {
                contextMenu = document.createElement('div');
                contextMenu.classList.add('table-dropdown-items', 'table-dropdown-items-context-menu');
                document.addEventListener('click', event => {
                    if (contextMenu.classList.contains('show') && !event.target.closest('.table-dropdown-items-context-menu') && !event.target.closest('.table-dropdown')) {
                        contextMenu.classList.remove('show');
                    }
                });
            }
            contextMenu.classList.add('show');
            contextMenu.innerHTML = dropdownItems.innerHTML;
            contextMenu.style.position = 'absolute';
            let width = window.getComputedStyle(dropdownItems).width ? parseInt(window.getComputedStyle(dropdownItems).width) : 0;
            contextMenu.style.left = (event.pageX-width) + 'px';
            contextMenu.style.top = event.pageY + 'px';
            document.body.appendChild(contextMenu);
        };
    });
    document.querySelectorAll('td .status').forEach(dropdownElement => {
        dropdownElement.onclick = event => {
            event.preventDefault();
            let dropdownItems = dropdownElement.querySelector('.status-dropdown');
            let contextMenu = document.querySelector('.status-dropdown-items-context-menu');
            if (!contextMenu) {
                contextMenu = document.createElement('div');
                contextMenu.classList.add('status-dropdown', 'status-dropdown-items-context-menu');
                document.addEventListener('click', event => {
                    if (contextMenu.classList.contains('show') && !event.target.closest('.status-dropdown-items-context-menu') && !event.target.closest('td .status')) {
                        contextMenu.classList.remove('show');
                    }
                });
            }
            contextMenu.classList.add('show');
            contextMenu.innerHTML = dropdownItems.innerHTML;
            contextMenu.style.position = 'absolute';
            let width = window.getComputedStyle(dropdownItems).width ? parseInt(window.getComputedStyle(dropdownItems).width) : 0;
            contextMenu.style.left = (event.pageX-width) + 'px';
            contextMenu.style.top = event.pageY + 'px';  
            document.body.appendChild(contextMenu);
            statusHandler();
        };
    });
};
initTableDropdown();
document.body.addEventListener('click', event => {
    if (!event.target.closest('.multiselect')) {
        document.querySelectorAll('.multiselect .list').forEach(element => element.style.display = 'none');
    } 
});
const initMultiselect = () => {
    document.querySelectorAll('.multiselect').forEach(element => {
        let updateList = () => {
            element.querySelectorAll('.item').forEach(item => {
                element.querySelectorAll('.list span').forEach(newItem => {
                    if (item.dataset.value == newItem.dataset.value) {
                        newItem.style.display = 'none';
                    }
                });
                item.querySelector('.remove').onclick = () => {
                    element.querySelector('.list span[data-value="' + item.dataset.value + '"]').style.display = 'flex';
                    item.querySelector('.remove').parentElement.remove();
                };
            });
            if (element.querySelectorAll('.item').length > 0) {
                element.querySelector('.search').placeholder = '';
            }
        };
        element.onclick = () => element.querySelector('.search').focus();
        element.querySelector('.search').onfocus = () => element.querySelector('.list').style.display = 'flex';
        element.querySelector('.search').onclick = () => element.querySelector('.list').style.display = 'flex';
        element.querySelector('.search').onkeyup = () => {
            element.querySelector('.list').style.display = 'flex';
            element.querySelectorAll('.list span').forEach(item => {
                item.style.display = item.innerText.toLowerCase().includes(element.querySelector('.search').value.toLowerCase()) ? 'flex' : 'none';
            });
            updateList();
        };
        element.querySelectorAll('.list span').forEach(item => item.onclick = () => {
            item.style.display = 'none';
            let html = `
                <span class="item" data-value="${item.dataset.value}">
                    <i class="remove">&times;</i>${item.innerText}
                    <input type="hidden" name="${element.dataset.name}" value="${item.dataset.value}">    
                </span>
            `;
            if (element.querySelector('.item')) {
                let ele = element.querySelectorAll('.item');
                ele = ele[ele.length-1];
                ele.insertAdjacentHTML('afterend', html);                          
            } else {
                element.insertAdjacentHTML('afterbegin', html);
            }
            element.querySelector('.search').value = '';
            updateList();
        });
        updateList();
    });
};
initMultiselect();
document.querySelectorAll('.msg').forEach(element => {
    element.querySelector('.close').onclick = () => {
        element.remove();
        history.replaceState && history.replaceState(null, '', location.pathname + location.search.replace(/[\?&]success_msg=[^&]+/, '').replace(/^&/, '?') + location.hash);
        history.replaceState && history.replaceState(null, '', location.pathname + location.search.replace(/[\?&]error_msg=[^&]+/, '').replace(/^&/, '?') + location.hash);
    };
});
if (location.search.includes('success_msg') || location.search.includes('error_msg')) {
    history.replaceState && history.replaceState(null, '', location.pathname + location.search.replace(/[\?&]success_msg=[^&]+/, '').replace(/^&/, '?') + location.hash);
    history.replaceState && history.replaceState(null, '', location.pathname + location.search.replace(/[\?&]error_msg=[^&]+/, '').replace(/^&/, '?') + location.hash);
}
const modal = options => {
    let element;
    if (document.querySelector(options.element)) {
        element = document.querySelector(options.element);
    } else if (options.modalTemplate) {
        document.body.insertAdjacentHTML('beforeend', options.modalTemplate());
        element = document.body.lastElementChild;
    }
    options.element = element;
    options.open = obj => {
        element.style.display = 'flex';
        element.getBoundingClientRect();
        element.classList.add('open');
        if (options.onOpen) options.onOpen(obj);
    };
    options.close = obj => {
        if (options.onClose) {
            let returnCloseValue = options.onClose(obj);
            if (returnCloseValue !== false) {
                element.style.display = 'none';
                element.classList.remove('open');
                element.remove();
            }
        } else {
            element.style.display = 'none';
            element.classList.remove('open');
            element.remove();
        }
    };
    if (options.state == 'close') {
        options.close({ source: element, button: null });
    } else if (options.state == 'open') {
        options.open({ source: element }); 
    }
    element.querySelectorAll('.dialog-close').forEach(e => {
        e.onclick = event => {
            event.preventDefault();
            options.close({ source: element, button: e });
        };
    });
    return options;
};
function insertTextAtCursor(el, text, minus = 0) {
    let val = el.value, endIndex, range;
    if (typeof el.selectionStart != "undefined" && typeof el.selectionEnd != "undefined") {
        endIndex = el.selectionEnd;
        el.value = val.slice(0, el.selectionStart) + text + val.slice(endIndex);
        el.selectionStart = el.selectionEnd = endIndex + text.length - minus;
    } else if (typeof document.selection != "undefined" && typeof document.selection.createRange != "undefined") {
        el.focus();
        range = document.selection.createRange();
        range.collapse(false);
        range.text = text;
        range.select();
    }
}
if (document.querySelector('.format-btn')) {
    document.querySelector('.format-btn.div').onclick = event => {
        event.preventDefault();
        insertTextAtCursor(document.querySelector('#content'), '<div></div>');
    };
    document.querySelector('.format-btn.heading').onclick = event => {
        event.preventDefault();
        insertTextAtCursor(document.querySelector('#content'), '<h1></h1>');
    };
    document.querySelector('.format-btn.paragraph').onclick = event => {
        event.preventDefault();
        insertTextAtCursor(document.querySelector('#content'), '<p></p>');
    };
    document.querySelector('.format-btn.strong').onclick = event => {
        event.preventDefault();
        insertTextAtCursor(document.querySelector('#content'), '<strong></strong>');
    };
    document.querySelector('.format-btn.italic').onclick = event => {
        event.preventDefault();
        insertTextAtCursor(document.querySelector('#content'), '<i></i>');
    };
    document.querySelector('.format-btn.image').onclick = event => {
        event.preventDefault();
        insertTextAtCursor(document.querySelector('#content'), '<img src="" width="" height="" alt="">');
    };
    document.getElementById('content').onkeydown = function(event) {
        if (event.key == 'Tab') {
            let start = this.selectionStart, end = this.selectionEnd, target = event.target, value = target.value;
            target.value = value.substring(0, start) + "\t" + value.substring(end);
            this.selectionStart = this.selectionEnd = start + 1;
            event.preventDefault();
        }
    };
    document.querySelector('.preview-btn a').onclick = event => {
        event.preventDefault();
        let content = document.getElementById('content').value;
        if (content == '') {
            document.getElementById('content').focus();
        } else {
            modal({
                state: 'open',
                modalTemplate: function() {
                    return `
                    <div class="dialog large">
                        <div class="content">
                            <h3 class="heading">Preview<span class="dialog-close">&times;</span></h3>
                            <div class="body">
                                <iframe class="dcontent" style="border:7px solid #fff;width:100%;height:400px;"></iframe>
                            </div>
                            <div class="footer pad-5">
                                <a href="#" class="btn dialog-close">Close</a>
                            </div>
                        </div>
                    </div>
                    `;
                },
                onOpen: function() {
                    document.querySelector('.dcontent').srcdoc = `
                    <!DOCTYPE html>
                    <html>
                        <head>
                            <meta charset="utf-8">
                            <meta name="viewport" content="width=device-width,minimum-scale=1">
                            <title>Newsletter</title>
                        </head>
                        <body style="padding:0;margin:0;">
                            ${content}
                        </body>
                    </html>`;
                }
            });    
        }    
    };
}
const recipientErrorHandler = () => {
    document.querySelectorAll('.recipient-error').forEach(element => {
        element.onclick = event => {
            event.preventDefault();
            modal({
                state: 'open',
                modalTemplate: function() {
                    return `
                    <div class="dialog">
                        <div class="content">
                            <h3 class="heading">View${element.classList.contains('unsub-msg') ? '' : ' Error'} Message<span class="dialog-close">&times;</span></h3>
                            <div class="body">
                                <p style="padding:10px 20px">${element.title}</p>
                            </div>
                            <div class="footer pad-5">
                                <a href="#" class="btn dialog-close">Close</a>
                            </div>
                        </div>
                    </div>
                    `;
                }
            }); 
        };
    });
};
recipientErrorHandler();
const statusHandler = () => {
    document.querySelectorAll('.status-dropdown a').forEach(link => link.onclick = event => {
        event.preventDefault();
        fetch(`campaigns.php?update=${link.dataset.id}&status=${link.dataset.value}`, { cache: 'no-store' }).then(response => response.text()).then(data => {
            if (data.includes('success')) {
                document.querySelector('.status[data-id="' + link.dataset.id + '"] span').className = link.dataset.value.toLowerCase();
                document.querySelector('.status[data-id="' + link.dataset.id + '"] span').title = link.dataset.value;
                link.closest('.status-dropdown').classList.remove('show');
            }
        });
    });
};
statusHandler();
document.querySelectorAll('.multi-checkbox').forEach(element => {
    element.querySelector('.check-all input[type="checkbox"]').onclick = event => {
        element.querySelectorAll('.con input[type="checkbox"]').forEach(element => element.checked = event.target.checked ? true : false);
    };
    element.querySelector('.check-all input[type="text"]').onkeyup = event => {
        element.querySelectorAll('.con .item').forEach(item => {
            item.style.display = item.innerText.toLowerCase().includes(element.querySelector('.check-all input[type="text"]').value.toLowerCase()) ? 'flex' : 'none';
        });
    };
});
if (document.querySelector('.ajax-update') && ajax_updates) {
    setInterval(() => {
        fetch(window.location.href, { cache: 'no-store' }).then(response => response.text()).then(data => {
            let doc = (new DOMParser()).parseFromString(data, 'text/html');
            for (let i = 0; i < document.querySelectorAll('.ajax-update').length; i++) {
                document.querySelectorAll('.ajax-update')[i].innerHTML = doc.querySelectorAll('.ajax-update')[i].innerHTML;
            }
            statusHandler();
            recipientErrorHandler();
            initTableDropdown();
        });
    }, ajax_interval); 
}
if (document.querySelector('.send-mail-form')) {
    document.querySelector('.add-additional-recipients').onclick = event => {
        event.preventDefault();
        modal({
            state: 'open',
            modalTemplate: function() {
                return `
                <div class="dialog additional-recipients-modal">
                    <div class="content">
                        <h3 class="heading">Custom Recipients<span class="dialog-close">&times;</span></h3>
                        <div class="body">
                            <form class="form size-full">
                                <textarea name="additional_recipients" placeholder="Email 1\nEmail 2\nEmail 3" style="height:120px"></textarea>
                            </form>
                        </div>
                        <div class="footer pad-5">
                            <a href="#" class="btn dialog-close">Save</a>
                        </div>
                    </div>
                </div>
                `;
            },
            onClose: function() {
                let additional_recipients = document.querySelector('.additional-recipients-modal textarea').value.split('\n');
                additional_recipients = additional_recipients.filter(n => n);
                additional_recipients = additional_recipients.filter(email => email.match(/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/));
                additional_recipients.forEach((recipient, index) => {
                    let recipientElement = document.querySelector(`input[type="checkbox"][value="${recipient}"]`);
                    if (recipientElement) {
                        recipientElement.checked = true;
                    } else {
                        let html = `
                            <div class="item">
                                <input id="checkbox-additional-${index}" type="checkbox" name="recipients[]" value="${recipient}" checked>
                                <label for="checkbox-additional-${index}">${recipient}</label>
                            </div>
                        `;
                        document.querySelector('.recipients-multi-checkbox .con').insertAdjacentHTML('beforeend', html);
                    }
                });
            }
        });
    };
    document.querySelector('.send-mail-form').onsubmit = event => {
        event.preventDefault();
        let recipients = [], recipientIndex = 0, recipientsCompleted = 0, error_msg = '', num_failed = 0, attachments = [], attachmentsUploaded = false;
        document.querySelectorAll('input[type="checkbox"]:checked').forEach(item => {
            if (item.id != 'check-all') {
                recipients.push(item.value);
            }
        });
        recipients = recipients.filter(n => n);
        if (typeof tinymce != 'undefined') {
            tinymce.get('content').save();
        }
        if (recipients.length == 0) {
            error_msg = 'Please select at least one recipient!';
        }
        if (document.querySelector('input[name="subject"]').value == '') {
            error_msg = 'Please provide the subject!';
        }
        if (document.querySelector('textarea[name="content"]').value == '') {
            error_msg = 'Please provide the content!';
        }
        if (error_msg != '') {
            modal({
                state: 'open',
                modalTemplate: function() {
                    return `
                    <div class="dialog">
                        <div class="content">
                            <h3 class="heading">Error<span class="dialog-close">&times;</span></h3>
                            <div class="body">
                                <p style="padding:10px 20px">${error_msg}</p>
                            </div>
                            <div class="footer pad-5">
                                <a href="#" class="btn dialog-close">Close</a>
                            </div>
                        </div>
                    </div>
                    `;
                }
            });
        } else {
            document.querySelectorAll('input[name="attachments[]"]').forEach(attachment => {
                if (attachment.files[0] != undefined) {
                    attachments.push(attachment.files[0]);
                }
            });
            let formAttachments = new FormData();
            attachments.forEach(attachment => formAttachments.append('attachments[]', attachment));
            document.querySelectorAll('input[name="attachments[]"]').forEach(attachment => attachment.remove());
            if (attachments.length) {
                fetch('sendmail.php', {
                    cache: 'no-store',
                    method: 'POST',
                    body: formAttachments
                }).then(response => response.json()).then(data => {
                    attachments = data;
                    attachmentsUploaded = true;
                });
            } else {
                attachmentsUploaded = true;
            }
            let recipientInterval = setInterval(() => {
                if (recipients.length && recipients[recipientIndex] && attachmentsUploaded) {
                    let formData = new FormData(document.querySelector('.send-mail-form'));
                    let recipient = recipients[recipientIndex];
                    formData.append('recipient', recipient);
                    attachments.forEach(attachment => formData.append('attachments[]', attachment));
                    fetch('sendmail.php', { 
                        cache: 'no-store',
                        method: 'POST',
                        body:  formData
                    }).then(response => response.text()).then(data => {
                        recipientsCompleted++;
                        document.querySelector('.num-recipients').innerHTML = `${recipientsCompleted}/${recipients.length} Recipients`;
                        if (recipientsCompleted == recipients.length && data == 'success') {
                            document.querySelector('.send-mail-modal .num-recipients').innerHTML = 'Finished!';
                        } else if (data != 'success') {
                            num_failed++;
                            document.querySelector('.send-mail-modal .num-recipients').innerHTML = `${recipientsCompleted}/${recipients.length} Recipients (${num_failed} Failed)`;
                            document.querySelector('.send-mail-modal .error-msg').innerHTML += `Failed to send mail to ${recipient}<br>(${data})<br><br>`;
                        }
                        if (recipientsCompleted == recipients.length) {
                            document.querySelector('.send-mail-modal .loader').style.display = 'none';
                        }
                    });
                    recipientIndex++;
                }
            }, ajax_interval); 
            modal({
                state: 'open',
                modalTemplate: function() {
                    return `
                    <div class="dialog send-mail-modal">
                        <div class="content">
                            <h3 class="heading">Sending Mail</h3>
                            <div class="body">
                                <p style="padding:10px 20px 0 20px;text-align:center">Please wait! Sending all mail...</p>
                                <div class="loader"></div>
                                <p class="num-recipients" style="padding:10px 20px;text-align:center;font-size:16px;font-weight:500;">0/${recipients.length} Recipients</p>
                                <p class="error-msg" style="margin:0;padding:0 20px 10px 20px;font-size:12px;font-weight:500;color:red;max-height:200px;overflow-y:auto;"></p>
                            </div>
                            <div class="footer pad-5">
                                <a href="#" class="btn dialog-close">Close</a>
                            </div>
                        </div>
                    </div>
                    `;
                },
                onClose: function() {
                    document.querySelector('.send-mail-form').reset();
                    recipients = [];
                    recipientIndex = 0;
                    recipientsCompleted = 0;
                    num_failed = 0;
                    attachments = [];
                    attachmentsUploaded = false;
                    error_msg = '';
                    clearInterval(recipientInterval);
                }
            });
        }
    };
}
const attachmentsHandler = () => {
    document.querySelectorAll('.attachment input').forEach(element => element.onchange = () => {
        element.parentElement.querySelector('span').innerHTML = element.files[0].name;
        if (element.closest('.attachment-wrapper').nextElementSibling && element.closest('.attachment-wrapper').nextElementSibling.classList.contains('attachment-wrapper')) {
            return;
        }
        element.closest('.attachment-wrapper').querySelector('.remove').style.display = 'inline-flex';
        element.closest('.attachments').insertAdjacentHTML('beforeend', `
            <div class="attachment-wrapper">
                <label class="attachment">
                    <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M384 480l48 0c11.4 0 21.9-6 27.6-15.9l112-192c5.8-9.9 5.8-22.1 .1-32.1S555.5 224 544 224l-400 0c-11.4 0-21.9 6-27.6 15.9L48 357.1 48 96c0-8.8 7.2-16 16-16l117.5 0c4.2 0 8.3 1.7 11.3 4.7l26.5 26.5c21 21 49.5 32.8 79.2 32.8L416 144c8.8 0 16 7.2 16 16l0 32 48 0 0-32c0-35.3-28.7-64-64-64L298.5 96c-17 0-33.3-6.7-45.3-18.7L226.7 50.7c-12-12-28.3-18.7-45.3-18.7L64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l23.7 0L384 480z"/></svg>
                    <span>Select File</span>
                    <input type="file" name="attachments[]">
                </label>
                <a href="#" class="remove">&times;</a>
            </div>
        `);
        document.querySelectorAll('.attachment-wrapper .remove').forEach(element => element.onclick = event => {
            event.preventDefault();
            element.parentElement.remove();
        });
        attachmentsHandler();
    });
    document.querySelectorAll('.attachment-wrapper .remove').forEach(element => element.onclick = event => {
        event.preventDefault();
        element.parentElement.remove();
    });
};
attachmentsHandler();
const previewNewsletter = event => {
    event.preventDefault();
    fetch(event.target.href).then(response => response.text()).then(data => {
        modal({
            state: 'open',
            modalTemplate: function() {
                return `
                <div class="dialog large">
                    <div class="content">
                        <h3 class="heading">Preview<span class="dialog-close">&times;</span></h3>
                        <div class="body">
                            <iframe class="dcontent" style="border:7px solid #fff;width:100%;height:400px;"></iframe>
                        </div>
                        <div class="footer pad-5">
                            <a href="#" class="btn dialog-close">Close</a>
                        </div>
                    </div>
                </div>
                `;
            },
            onOpen: function() {
                document.querySelector('.dcontent').srcdoc = data;
            }
        });   
    });  
};