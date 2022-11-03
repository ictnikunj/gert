import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';

export default class MoorlFormBuilderV2Plugin extends Plugin {

    static options = {
        useAjax: false,
        hiddenClass: 'd-none',
        hiddenSubmitSelector: '.submit--hidden',
        formContentSelector: '.form-content',
        cmsBlock: '.cms-block',
        contentType: 'application/x-www-form-urlencoded',
    };

    init() {
        this._client = new HttpClient();
        this._getButton();
        this._getHiddenSubmit();
        this._inheritGetParams();
        this._registerEvents();
        this._getCmsBlock();
        this._getConfirmationText();
        this._dynamicForm();
        this._initRepeater();
    }

    sendAjaxFormSubmit() {
        const { _client, el, options } = this;
        const _data = new FormData(el);

        _client.post(el.action, _data, this._handleResponse.bind(this), options.contentType);
    }

    _inheritGetParams() {
        const that = this;
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.forEach(function (value, name) {
            if (that.el.querySelector('[name=' + name + ']')) {
                that.el.querySelector('[name=' + name + ']').value = value;
            }
        });
    }

    _registerEvents() {
        const that = this;

        $(that.el).on('click', '[data-action-remove]', function () {
            that._client.get(this.dataset.actionRemove, that._handleResponse.bind(that));
        });

        $(that.el).find("[data-autocomplete]").each(function() {
            let url = this.dataset.autocomplete;
            $(this).autocomplete({
                bootstrapVersion: 4,
                limit: 10,
                request: {
                    url: url,
                    queryParam: "q"
                }
            });
        });

        $(that.el).find("[data-excluded-days]").each(function() {
            this.addEventListener('change', function(e) {
                let day = new Date(this.value).getUTCDay();
                let excluded = JSON.parse(this.dataset.excludedDays);
                if (isNaN(day) || excluded.length === 0) {
                    return;
                }
                if (excluded.includes(day)) {
                    e.target.value = '';
                    e.target.setCustomValidity('weekday not allowed');
                } else {
                    e.target.setCustomValidity('');
                }
            });
        });

        $(that.el).on("change", ".custom-file-input", function () {
            let fileName = $(this).val().split("\\").pop();
            $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
        });

        $(that.el).find("input,textarea,select").on("keyup change force", function () {
            that._dynamicForm();
            let name = this.name;
            let keyword = $(this).val();
            name = name.replace(/[^\w+]/gi,''); // remove invalid characters
            $(that.el).find("[data-form-filter-parent="+ name +"]").each(function () {
                $(this).hide();
                if (this.dataset.formFilterKeywords.includes(keyword)) {
                    $(this).show();
                }
            });
        });

        if (this.options.useAjax) {
            this.el.addEventListener('submit', this._handleSubmit.bind(this));

            if (this._button) {
                this._button.addEventListener('submit', this._handleSubmit.bind(this));
                this._button.addEventListener('click', this._handleSubmit.bind(this));
            }
        }
    }

    _getConfirmationText() {
        const input = this.el.querySelector('input[name="confirmationText"]');
        if (input) {
            this._confirmationText = input.value;
        }
    }

    _getButton() {
        this._button = this.el.querySelector('button[type="submit"]');
    }

    _getCmsBlock() {
        this._block = this.el.closest(this.options.cmsBlock);
    }

    _getHiddenSubmit() {
        this._hiddenSubmit = this.el.querySelector(this.options.hiddenSubmitSelector);
    }

    _handleSubmit(event) {
        if (typeof event != 'undefined') {
            event.preventDefault();
        }

        if (this.el.checkValidity()) {
            this._submitForm();
        } else {
            this._showValidation();
        }
    }

    _showValidation() {
        this._hiddenSubmit.click();
    }

    _submitForm() {
        this.$emitter.publish('beforeSubmit');
        this._button.disabled = true;
        this._button.classList.add("loading");

        this.sendAjaxFormSubmit();
    }

    _handleResponse(res) {
        const response = JSON.parse(res);
        const that = this;
        this.$emitter.publish('onFormResponse', res);
        this._button.disabled = false;

        if (response.length > 0) {
            response.forEach(function (responseItem) {
                if (responseItem.removeId) {
                    $(responseItem.removeId).remove();
                }
                if (responseItem.type === 'success') {
                    that.el.classList.remove("was-validated");
                    that.el.reset();

                    $(that.el).find(".custom-file-input").each(function () {
                        let label = $(this).attr('placeholder');
                        $(this).siblings(".custom-file-label").removeClass("selected").html(label);
                    });
                    that._button.classList.remove("loading");
                }
                if (responseItem.alert) {
                    $(that.el).find('.moorl-form-builder-feedback').html(responseItem.alert);
                    that._button.classList.remove("loading");
                }
                if (responseItem.reload === true) {
                    setTimeout(function () {
                        window.location.reload();
                    }, 2000);
                }
                if (responseItem.redirectTo) {
                    setTimeout(function () {
                        window.location = responseItem.redirectTo;
                    }, 2000);
                }
            });
        } else {
            window.location.reload();
        }
    }

    _createResponse(changeContent, content) {
        if (changeContent) {
            if (this._confirmationText) {
                content = this._confirmationText;
            }
            this._block.innerHTML = `<div class="confirm-message">${content}</div>`;
        } else {
            const confirmDiv = this._block.querySelector('.confirm-alert');
            if (confirmDiv) {
                confirmDiv.remove();
            }
            const html = `<div class="confirm-alert">${content}</div>`;
            this._block.insertAdjacentHTML('beforeend', html);
        }

        this._block.scrollIntoView({
            behavior: 'smooth',
            block: 'end',
        });
    }

    _compare(value1, value2, type) {
        if (!Array.isArray(value1)) {value1 = value1.split(";");}
        if (!Array.isArray(value2)) {value2 = value2.split(";");}
        switch (type) {
            case 'is':
                return (value1.filter(element => value2.includes(element)).length > 0);
            case 'not':
                return (value1.filter(element => value2.includes(element)).length === 0);
            case 'gt':
                return (parseFloat(value1[0]) > parseFloat(value2[0]));
            case 'lt':
                return (parseFloat(value1[0]) < parseFloat(value2[0]));
            case 'contains':
                return value2.some(element => value1[0].includes(element));
        }
    }

    _dynamicForm() {
        const {el} = this;
        const that = this;
        $(el).find('[data-form-conditions]').each(function () {
            const _that = this;
            let conditions = this.dataset.formConditions;
            try {
                conditions = JSON.parse(conditions);
                let conditionCount = conditions ? conditions.length : 0;
                if (conditionCount === 0) {return;}
                let passedConditions = 0;
                conditions.forEach(function(condition) {
                    let expectedVal = $(el).find('[name='+ condition.name +']').val();
                    if ($(el).find('input[type=checkbox][name='+ condition.name +']').length > 0) {
                        let checkValue = $(el).find('input[type=checkbox][name='+ condition.name +']').prop('checked');
                        expectedVal = checkValue ? '1' : '0';
                    }
                    if ($(el).find('input[type=radio][name='+ condition.name +']').length > 0) {
                        expectedVal = 'undefined';
                        $(el).find('input[type=radio][name='+ condition.name +']').each(function () {
                            if ($(this).prop("checked")) {
                                expectedVal = this.value;
                            }
                        });
                    }
                    if (expectedVal && that._compare(expectedVal, condition.value, condition.type)) {
                        passedConditions++;
                    }
                });
                if (conditionCount === passedConditions) {
                    that._show(_that)
                } else {
                    that._hide(_that);
                }
            } catch (e) {
            }
        });
    }

    _show(el) {
        $(el).addClass('animated fadeInUp');
        $(el).show();
        $(el).find('[data-required]').each(function () {
            this.setAttribute('required', true);
        });
    }

    _hide(el) {
        $(el).removeClass('animated fadeInUp');
        $(el).hide();
        $(el).find('[required]').each(function () {
            this.setAttribute('data-required', true);
            this.removeAttribute('required');
        });
        $(el).find("input[type=text],input[type=password],input[type=number],textarea,select").each(function () {
            this.value = null;
        });
        $(el).find("input[type=checkbox],input[type=radio]").each(function () {
            this.checked = false;
        });
    }

    _initRepeater() {
        const that = this;
        $(that.el).find('[data-form-repeater]').each(function () {
            try {
                const repeater = {};
                repeater.count = 0;
                repeater.id = this.dataset.formRepeater;
                repeater.numberMin = this.dataset.numberMin;
                repeater.numberMax = this.dataset.numberMax;
                repeater.element = document.getElementById(repeater.id);
                repeater.template = repeater.element.innerHTML;
                repeater.element.innerHTML = "";
                repeater.element.classList.remove('d-none');
                const addButton = this.querySelector('button.moorl-form-builder-add');
                const removeButton = this.querySelector('button.moorl-form-builder-remove');
                for (;repeater.count < repeater.numberMin;) {
                    that._addRepeaterElement(repeater);
                }
                addButton.addEventListener('click', () => {
                    that._addRepeaterElement(repeater);
                });
                removeButton.addEventListener('click', () => {
                    repeater.element.lastElementChild.remove();
                });
            } catch (e) {
            }
        });
    }

    _addRepeaterElement(repeater) {
        const re = /repeaterCount/gi;
        repeater.element.insertAdjacentHTML('beforeend', '<div class="list-group-item">' + repeater.template.replace(re, repeater.count) + '</div>');
        repeater.count++;
    }
}
