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
const addClient = () => {
    modal({
        state: 'open',
        modalTemplate: function() {
            return `
            <div class="dialog add-client-modal">
                <div class="content">
                    <h3 class="heading">Add Client<span class="dialog-close">&times;</span></h3>
                    <div class="body">
                        <form class="form">
                            <label for="email">Email</label>
                            <input id="email" type="email" name="email" placeholder="Email" required>
                
                            <label for="first_name">First Name</label>
                            <input id="first_name" type="text" name="first_name" placeholder="First Name" required>
                
                            <label for="last_name">Last Name</label>
                            <input id="last_name" type="text" name="last_name" placeholder="Last Name">
                
                            <label for="phone">Phone</label>
                            <input id="phone" type="text" name="phone" placeholder="Phone">
                
                            <label for="address_street">Address</label>
                            <input id="address_street" type="text" name="address_street" placeholder="Street">
                
                            <label for="address_city">City</label>
                            <input id="address_city" type="text" name="address_city" placeholder="City">
                
                            <label for="address_state">State</label>
                            <input id="address_state" type="text" name="address_state" placeholder="State">
                
                            <label for="address_zip">Zip</label>
                            <input id="address_zip" type="text" name="address_zip" placeholder="Zip">
                
                            <label for="address_country">Country</label>
                            <select id="address_country" name="address_country">
                                ${countries.map(country => `<option value="${country}">${country}</option>`).join('')}
                            </select>

                            <span class="error-msg"></span>
                        </form>
                    </div>
                    <div class="footer pad-5">
                        <a href="#" class="btn dialog-close save">Add</a>
                    </div>
                </div>
            </div>
            `;
        },
        onClose: function(event) {
            if (event && event.button && event.button.classList.contains('save')) {
                let form = event.source.querySelector('form');
                fetch('ajax.php?action=add_client', {
                    method: 'POST',
                    body: new FormData(form),
                    cache: 'no-cache'
                }).then(response => response.json()).then(res => {
                    if (res.status == 'error') {
                        form.querySelector('.error-msg').innerText = res.message;
                        form.querySelector('.error-msg').scrollIntoView();
                    }
                    if (res.status == 'success') {
                        let select = document.querySelector('.client_id');
                        select.insertAdjacentHTML('beforeend', `<option value="${res.client_id}" selected>${form.querySelector('#first_name').value + ' ' + form.querySelector('#last_name').value}</option>`);
                        select.value = res.client_id;
                        event.source.querySelector('.dialog-close').click();
                    }
                });
                return false;
            }
        }
    });
};
const initManageInvoiceItems = () => {
    document.querySelector('.add-item').onclick = event => {
        event.preventDefault();
        document.querySelector('.manage-invoice-table tbody').insertAdjacentHTML('beforeend', `
        <tr>
            <td><input type="hidden" name="item_id[]" value="0"><input name="item_name[]" type="text" placeholder="Name"></td>
            <td><input name="item_description[]" type="text" placeholder="Description"></td>
            <td><input name="item_price[]" type="number" placeholder="Price" step=".01"></td>
            <td><input name="item_quantity[]" type="number" placeholder="Quantity"></td>
            <td><svg class="delete-item" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" /></svg></td>
        </tr>
        `);
        document.querySelectorAll('.delete-item').forEach(element => element.onclick = event => {
            event.preventDefault();
            element.closest('tr').remove();
        });
        if (document.querySelector('.no-invoice-items-msg')) {
            document.querySelector('.no-invoice-items-msg').remove();
        }
    };
    document.querySelectorAll('.delete-item').forEach(element => element.onclick = event => {
        event.preventDefault();
        element.closest('tr').remove();
    });
    document.querySelector('.add-client').onclick = event => {
        event.preventDefault();
        addClient();
    };
    document.querySelector('#recurrence').onchange = () => {
        document.querySelector('.recurrence-options').style.display = document.querySelector('#recurrence').value == 1 ? 'block' : 'none';
    };
};
if (document.querySelector('.quick-create-invoice')) {
    document.querySelector('.quick-create-invoice').onclick = event => {
        event.preventDefault();
        fetch('invoice.php', { cache: 'no-cache' }).then(response => response.text()).then(data => {
            let html = (new DOMParser()).parseFromString(data, 'text/html');
            let form = html.querySelector('.form');
            let table = html.querySelector('.manage-invoice-table');
            table.style.display = 'block';
            table.style.overflowX = 'visible';
            modal({
                state: 'open',
                modalTemplate: function() {
                    return `
                    <div class="dialog create-invoice-modal">
                        <div class="content">
                            <h3 class="heading">Quick Create Invoice<span class="dialog-close">&times;</span></h3>
                            <div class="body">
                                <form class="form">
                                    ${form.innerHTML}
                                    ${table.outerHTML}
                                    <span class="error-msg"></span>
                                </form>
                            </div>
                            <div class="footer pad-5">
                                <a href="#" class="btn dialog-close save">Save</a>
                            </div>
                        </div>
                    </div>
                    `;
                },
                onOpen: function(event) {
                    initMultiselect();
                    initManageInvoiceItems();
                },
                onClose: function(event) {
                    if (event && event.button && event.button.classList.contains('save')) {
                        let form = event.source.querySelector('form');
                        fetch('ajax.php?action=create_invoice', {
                            method: 'POST',
                            body: new FormData(form),
                            cache: 'no-cache'
                        }).then(response => response.json()).then(res => {
                            if (res.status == 'error') {
                                form.querySelector('.error-msg').innerText = res.message;
                                form.querySelector('.error-msg').scrollIntoView();
                            }
                            if (res.status == 'success') {
                                event.source.querySelector('.dialog-close').click();
                                if (confirm('Invoice created. Would you like to view it?')) {
                                    location.href = 'view_invoice.php?id=' + res.invoice_id;
                                }
                            }
                        });
                        return false;
                    }
                }
            });   
        });   
    };  
}