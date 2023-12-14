BX.ready(function () {
    document.querySelector('form#uploadModalForm').addEventListener(
        'submit',
        function (e) {
            e.preventDefault();
            showInfo('', [], false);

            let form = new FormData(this);
            let fileInput = e.currentTarget.querySelectorAll('input[type=file]');
            if (fileInput) {
                fileInput.forEach((input) => {
                    const inputName = input.getAttribute('name');
                    for (let file in input.files) {
                        form.append(inputName, input.files[file])
                    }
                })
            }

            BX.ajax.runComponentAction(
                'test:uploadModal',
                "save",
                {
                    mode: 'class',
                    data: form
                }
            ).then(
                (response) => {
                    if (response.status === "success" && response.data.result === true) {
                        document.querySelector('form#uploadModalForm').reset();
                        showInfo('Данные успешно отправлены', ['alert', 'alert-success'], true);
                    } else {
                        throw ('Ошибка сохранения данных');
                    }
                }
            ).catch((error) => {
                let errorMsg = [];
                error.errors.forEach((item) => {
                    errorMsg.push(item.message)
                })
                showInfo(errorMsg.join('<br>'), ['alert', 'alert-danger'], true);
                setInterval(() => {
                    showInfo('', [], false);
                }, 10000);
            });
        }
    );

    function showInfo(msg = '', classList = [], show = true) {
        let msgSelector = document.querySelector('form#uploadModalForm #infoMsg');
        msgSelector.classList.add(...classList);
        msgSelector.innerHTML = msg;
        msgSelector.style.display = (show === true) ? 'block' : 'none';
    }
});