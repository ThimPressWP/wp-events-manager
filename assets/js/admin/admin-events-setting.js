document.addEventListener('DOMContentLoaded', function () {
    // editor settings
    var editorSettings = {
        selector: '.custom-editor',
        media_buttons: false,
        height: 100,
    }
    tinymce.init(editorSettings);

    var formCount = 1;

    var formInfoContainer = document.querySelector('.form_info-container');

    var sortable = new Sortable(formInfoContainer, {
        handle: '.form_info-header-left',
        animation: 150,
    });

    // click Add more
    document.getElementById('add_form_info-btn').addEventListener('click', function (e) {
        e.preventDefault();
        var newFormInfo = document.createElement('div');
        newFormInfo.className = 'form_info';
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
                    <input type="text" name="field_label[]">
                </div>
                <div class="field-content-desc">
                    <label>Description:</label>
                    <div class="custom-editor"></div>
                </div>
            </div>
        `;
        // add new form_info to the container
        document.querySelector('.form_info-container').appendChild(newFormInfo);

        // initialize editor
        var customEditor = newFormInfo.querySelector('.custom-editor');
        tinymce.init(editorSettings, { target: customEditor });

        formCount++;

        // delete form
        var deleteButotn = newFormInfo.querySelector('.dashicons-no');
        deleteButotn.addEventListener('click', function(e) {
            e.preventDefault();
            if(formCount > 1) {
                newFormInfo.remove();
                formCount--;
            } else {
                var titleInput = newFormInfo.querySelector('.field-content-title input');
                var descriptionEditor = newFormInfo.querySelector('.custom-editor');
                titleInput = '';
                tinymce.get(descriptionEditor).setContent('');
            }
        });

        // hidden form_info-content
        var hiddenButton = newFormInfo.querySelector('.dashicons-minus');
        var formContent = newFormInfo.querySelector('.form_info-content');
        hiddenButton.addEventListener('click', function(e) {
            e.preventDefault();
            formContent.classList.toggle('hidden');
        })
    });

    // var addButton = document.getElementById('add_form_info-btn');
    // var formInfoContainer = document.querySelector('.form_info-container');

    // addButton.addEventListener('click', function (e) {
    //     e.preventDefault();
    //     formInfoContainer.innerHTML += formInfoTemplate;
    // });

    // formInfoContainer.addEventListener('click', function (e) {
    //     // get the closest HTML element with class "form_info"
    //     var formInfo = e.target.closest('.form_info');

    //     if (e.target.classList.contains('dashicon-no')) {
    //         deleteFormInfo(formInfo);
    //     } else if (e.target.classList.contains('dashicon-minus')) {
    //         toggleFormContent(formInfo);
    //     }
    // });

    // var formInfoTemplate = `
    // <div class="form_info">
    //     <div class="form_info-header">
    //         <div class="form_info-header-left">
    //             <div class="dashicons-before dashicons-move"></div>
    //         </div>
    //         <div class="form_info-header-right">
    //             <button class="dashicons-before dashicons-no"></button>
    //             <button class="dashicons-before dashicons-minus"></button>
    //         </div>
    //     </div>
    //     <div class="form_info-content">
    //         <div class="field-content-title">
    //             <label>Title:</label>
    //             <input type="text" name="field_label[]">
    //         </div>
    //         <div class="field-content-desc">
    //             <label">Description:</label>
    //             <div class="custom-editor"></div>
    //         </div>
    //     </div>
    // </div>
    // `;

    // // delete form_info
    // function deleteFormInfo(formInfo) {
    //     if (formInfoContainer.childElementCount > 1) {
    //         formInfoContainer.removeChild(formInfo);
    //     } else {
    //         // clear the content of form_info
    //         var wpEditorId = formInfo.querySelector('.wp-editor-area').id;
    //         tinyMCE.get(wpEditorId).setContent('');
    //     }
    // }

    // toggle form_info content visibility
    // function toggleFormContent(formInfo) {
    //     var formInforContent = formInfo.querySelector('.form_info-content');
    //     if (formInforContent.style.display === 'none') {
    //         formInforContent.style.display === 'block';
    //     }
    // }
});