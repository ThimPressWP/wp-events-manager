document.addEventListener('DOMContentLoaded', function () {
    let formData = {};
    // function create tinymce editor
    function createEditor(container, formInfoId) {
        let editorSettings = {
            selector: '.custom-editor',
            media_buttons: false,
            height: 100,
            setup: function (editor) {
                editor.on('input', function () {
                    let editorContent = editor.getContent();
                    formData[formInfoId].description = editorContent
                    updateHiddenInput();
                });
            }
        };
        tinymce.init(editorSettings, { target: container });
    }

    let formInfoContainer = document.querySelector('.form_info-container');
    let customEditors = formInfoContainer.querySelectorAll('.custom-editor');

    function reinitEditor() {
        customEditors.forEach(function (editor) {
            let formInfoId = editor.getAttribute('data-form-id');
            createEditor(`#${formInfoId}`, formInfoId);
        });
    }
    reinitEditor();

    function initSortable() {
        new Sortable(formInfoContainer, {
            animation: 150,
            handle: '.form_info-header-left',
            onStart: function (e) {
                tinymce.remove();
            },
            onEnd: function (e) {
                let formInfoIds = Array.from(formInfoContainer.querySelectorAll('.form_info')).map(formInfo => formInfo.getAttribute('id'));
                // Update the form data object with the new order
                let newFormData = {};
                formInfoIds.forEach((formInfoId, index) => {
                    newFormData[formInfoId] = formData[formInfoId];
                });
                formData = newFormData;
                updateHiddenInput();
                reinitEditor();
            }
        });
    }

    initSortable();

    function handleFormInfo() {
        let newFormInfo = document.createElement('div');
        let formInfoId = Date.now();
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
                    <div class="custom-editor"></div>
                </div>
            </div>
        `;
        formInfoContainer.appendChild(newFormInfo);

        // initialize tinymce editor
        let customEditor = newFormInfo.querySelector('.custom-editor');
        createEditor(customEditor, formInfoId);

        let inputTitle = newFormInfo.querySelector('.field-content-title input');
        inputTitle.addEventListener('input', function () {
            formData[formInfoId].title = inputTitle.value;
            updateHiddenInput();
        });

        // delete form_info
        let deleteButton = newFormInfo.querySelector('.dashicons-no');
        deleteButton.addEventListener('click', function (e) {
            e.preventDefault();
            newFormInfo.remove();
            delete formData[formInfoId];
            updateHiddenInput();
        });

        // visibility form_info content
        let hiddenButton = newFormInfo.querySelector('.dashicons-minus');
        let contentSection = newFormInfo.querySelector('.form_info-content');
        hiddenButton.addEventListener('click', function (e) {
            e.preventDefault();
            contentSection.classList.toggle('hidden');
        });

        formData[formInfoId] = {
            title: "",
            description: ""
        };
        updateHiddenInput();
    }

    function updateHiddenInput() {
        let filteredData = Object.fromEntries(
            Object.entries(formData).filter(([key, value]) => value.title !== '' || value.description !== '')
        );

        let input = document.getElementById('tp_event_schedules');
        input.value = JSON.stringify(filteredData);
    }

    document.getElementById('add_form_info-btn').addEventListener('click', function (e) {
        e.preventDefault();
        handleFormInfo();
    });
});