jQuery(document).ready(function($) {
    document.getElementById("outboundtablink").click();
});

document.addEventListener("DOMContentLoaded", function () {
    const container = document.getElementById("link-mapper-groups");
    const addBtn = document.getElementById("add-mapper-group");

    let index = 0;

    const createGroup = (url = "", keyword = "", outbound = "", enabled = false, replace_mode = "first", nofollow = false, target = "_blank") => {
        const group = document.createElement("div");
        group.className = "mapper-group";
        group.innerHTML = `
            <input type="text" name="link_mapper[${index}][url]" placeholder="Blog Post URL" value="${url}" class="url-input" required />
            <span class="url-status"></span>
            <input type="text" name="link_mapper[${index}][keyword]" placeholder="Keyword(s)" value="${keyword}" class="keyword" required />
            <input type="text" name="link_mapper[${index}][outbound]" placeholder="Outbound Link" value="${outbound}" class="url" required />

            <label>
                <select name="link_mapper[${index}][replace_mode]">
                    <option value="all" ${replace_mode === 'all' ? 'selected' : ''}>All Instances</option>
                    <option value="first" ${replace_mode === 'first' ? 'selected' : ''}>First Only</option>
                </select>
            </label>

            <label>
                <select name="link_mapper[${index}][target]">
                    <option value="_self" ${target === '_self' ? 'selected' : ''}>Same Tab (_self)</option>
                    <option value="_blank" ${target === '_blank' ? 'selected' : ''}>New Tab (_blank)</option>
                    <option value="_new" ${target === '_new' ? 'selected' : ''}>New Window (_new)</option>
                </select>
            </label>

            <div class="field-enable-wrapper">
                <label class="toggle-switch">
                    <input type="checkbox" name="link_mapper[${index}][nofollow]" ${nofollow === true ? 'checked' : ''} />
                    <span class="slider"></span>
                </label>
                <span class="toggle-label">Nofollow</span>
            </div>

            <div class="field-enable-wrapper">
                <label class="toggle-switch">
                    <input type="checkbox" name="link_mapper[${index}][enabled]" ${enabled ? 'checked' : ''} />
                    <span class="slider"></span>
                </label>
                <span class="toggle-label">Enable</span>
            </div>

            <button type="button" class="remove-mapper-group button delete-button" title="Remove">
                <span class="dashicons dashicons-trash"></span>
            </button>
        `;
        container.appendChild(group);
        index++;
    };
    
    addBtn.addEventListener("click", () => {
        createGroup("", "", "", false, "first", true, "_blank");
    });

    container.addEventListener("click", (e) => {
        const removeBtn = e.target.closest(".remove-mapper-group");
        if (removeBtn) {
            removeBtn.parentElement.remove();
        }
    });
    
    if (typeof appLmSavedMappings !== "undefined" && Array.isArray(appLmSavedMappings) && appLmSavedMappings.length > 0) {
        appLmSavedMappings.forEach(item => {
            createGroup(
                item.url,
                item.keyword,
                item.outbound,
                item.enabled !== false,
                item.replace_mode || 'first',
                typeof item.nofollow !== "undefined" ? item.nofollow : false,
                item.target || '_blank'
            );
        });
    } else {
        createGroup("", "", "", false, "first", false, "_blank");
    }
});

document.addEventListener("input", function (e) {
    if (e.target.classList.contains("url-input")) {
        const input = e.target;
        const statusSpan = input.parentElement.querySelector(".url-status");

        clearTimeout(input.checkTimer);
        input.checkTimer = setTimeout(() => {
            fetch(AppLmAjax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'app_lm_check_url',
                    nonce: AppLmAjax.nonce,
                    url: input.value
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    statusSpan.textContent = '✅';
                    statusSpan.style.color = 'green';
                } else {
                    statusSpan.textContent = '❌';
                    statusSpan.style.color = 'red';
                }
            });
        }, 500);
    }
});
