document.addEventListener('DOMContentLoaded', function () {
    let formData = {};

    const schedulesInput = document.getElementById('tp_event_schedules');
    if (schedulesInput.value) {
        try {
            formData = JSON.parse(schedulesInput.value);
        } catch (error) {
            console.error("Error parsing existing schedules:", error);
        }
    }

    function createEditor(container, formInfoId, initialContent) {
        tinymce.init({
            selector: container,
            media_buttons: false,
            height: 100,
            setup: function (editor) {
                editor.setContent(initialContent);
                editor.on('input', function () {
                    formData[formInfoId].description = editor.getContent();
                    updateHiddenInput();
                });
            }
        });
    }

    function updateFormData(formInfoId,inputTitle) {
        formData[formInfoId].title = inputTitle.value;
        const editor = tinymce.get(formInfoId);
        if (editor) {
            formData[formInfoId].description = editor.getContent();
        }
        updateHiddenInput();
    }

    function deleteFormInfo(formInfoId) {
        delete formData[formInfoId];
        updateHiddenInput();
    }

    function toggleFormInfoContent(formInfoId) {
        const contentSection = document.getElementById(formInfoId).querySelector('.form_info-content');
        contentSection.classList.toggle('hidden');
    }

    function handleFormInfo(formInfoId) {
        if (!formData[formInfoId]) {
            formData[formInfoId] = {
                title: "",
                description: ""
            };
        }
        createEditor(`#${formInfoId} .custom-editor`, formInfoId, formData[formInfoId].description);
        updateHiddenInput();

        const inputTitle = document.getElementById(formInfoId).querySelector('.field-content-title input');
        inputTitle.value = formData[formInfoId].title;
        inputTitle.addEventListener('input', function () {
            updateFormData(formInfoId, inputTitle);
        });

        const deleteButton = document.getElementById(formInfoId).querySelector('.dashicons-no');
        deleteButton.addEventListener('click', function (e) {
            e.preventDefault();
            const formInfoElement = document.getElementById(formInfoId);
            formInfoElement.remove();
            deleteFormInfo(formInfoId);
        });

        const hiddenButton = document.getElementById(formInfoId).querySelector('.dashicons-minus');
        hiddenButton.addEventListener('click', function (e) {
            e.preventDefault();
            toggleFormInfoContent(formInfoId);
        });
    }

    function updateHiddenInput() {
        const filteredData = Object.fromEntries(
            Object.entries(formData).filter(([key, value]) => value.title !== '' || value.description !== '')
        );

        const input = document.getElementById('tp_event_schedules');
        input.value = JSON.stringify(filteredData);
    }

    const formInfoContainer = document.querySelector('.form_info-container');
    const customEditors = formInfoContainer.querySelectorAll('.custom-editor');

    document.getElementById('add_form_info-btn').addEventListener('click', function (e) {
        e.preventDefault();
        const formInfoId = Date.now();
        const newFormInfo = document.createElement('div');
        newFormInfo.className = 'form_info';
        newFormInfo.setAttribute('id', formInfoId);
        newFormInfo.innerHTML = `
            <div class="form_info-header">
                <div class="form_info-header-left">
                    <div class="dashicons-before dashicons-move"></div>
                </div>
                <div class="form_info-header-right">
                    <button class="dashicons-before dashicons-no"></button>
                    <button class="dashicons-before dashicons-minus"></button>
                </div>
            </div>
            <div class="form_info-content">
                <div class="field-content-title">
                    <label>Title:</label>
                    <input type="text">
                </div>
                <div class="field-content-desc">
                    <label>Description:</label>
                    <div class="custom-editor" data-form-id="${formInfoId}"></div>
                </div>
            </div>
        `;
        formInfoContainer.appendChild(newFormInfo);
        handleFormInfo(formInfoId);
    });

    // Restore existing form info elements
    const existingFormInfos = formInfoContainer.querySelectorAll('.form_info');
    existingFormInfos.forEach(formInfo => {
        const formInfoId = formInfo.getAttribute('id');
        handleFormInfo(formInfoId);
    });
});