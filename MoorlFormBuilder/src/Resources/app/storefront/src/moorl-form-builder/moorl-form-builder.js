import Plugin from 'src/plugin-system/plugin.class';
import FormSerializeUtil from 'src/utility/form/form-serialize.util';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';

export default class MoorlFormBuilder extends Plugin {

    static options = {};

    init() {
        this._client = new HttpClient(window.accessKey, window.contextToken);

        this._form = this.el;

        this._submitButton = this.el.querySelector('button[type="submit"]');

        this._conditions = [];

        this._repeater = [];

        this._formId = this.el.dataset.moorlFormBuilder;

        this._xhr = this.el.dataset.formXhr;

        this._registerEvents();

        this._dynamicForm();

        this._initRepeater();
        //this._captcha = document.querySelector("[data-form-conditions]");
    }

    _initRepeater() {
        const that = this;
        $(that._form).find('[data-form-repeater]').each(function () {
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
                console.log(e);
            }
        });
    }

    _addRepeaterElement(repeater) {
        const re = /repeaterCount/gi;
        repeater.element.insertAdjacentHTML('beforeend', '<div class="list-group-item">' + repeater.template.replace(re, repeater.count) + '</div>');
        repeater.count++;
    }

    _compare(value1, value2, type) {
        if (!Array.isArray(value1)) {
            value1 = value1.split(";");
        }
        if (!Array.isArray(value2)) {
            value2 = value2.split(";");
        }

        //console.log(value1, type ,value2);

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
        const that = this;
        $(that._form).find('[data-form-conditions]').each(function () {
            const _that = this;
            let conditions = this.dataset.formConditions;
            try {
                conditions = JSON.parse(conditions);

                let conditionCount = conditions ? conditions.length : 0;
                if (conditionCount === 0) {
                    return;
                }

                let passedConditions = 0;

                conditions.forEach(function(condition) {
                    let expectedVal = $(that._form).find('[name='+ condition.name +']').val();

                    if ($(that._form).find('input[type=checkbox][name='+ condition.name +']').length > 0) {
                        let checkValue = $(that._form).find('input[type=checkbox][name='+ condition.name +']').prop('checked');
                        expectedVal = checkValue ? '1' : '0';
                    }

                    if ($(that._form).find('input[type=radio][name='+ condition.name +']').length > 0) {
                        expectedVal = 'undefined';
                        $(that._form).find('input[type=radio][name='+ condition.name +']').each(function () {
                            if ($(this).prop("checked")) {
                                expectedVal = this.value;
                            }
                        });
                    }

                    if (expectedVal && that._compare(expectedVal, condition.value, condition.type)) {
                        console.log("Match");
                        passedConditions++;
                    }
                });

                //console.log(conditionCount, passedConditions);

                if (conditionCount === passedConditions) {
                    that._show(_that)
                } else {
                    that._hide(_that);
                }
            } catch (e) {
                console.log(e);
                return;
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

    _registerEvents() {
        const that = this;
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.forEach(function (value, name) {
            if (that._form.querySelector('[name=' + name + ']')) {
                that._form.querySelector('[name=' + name + ']').value = value;
            }
        });
        $(that._form).on('click', '[data-action-remove]', function () {
            // Todo: replace Confirm
            that._client.get(this.dataset.actionRemove, that._onLoaded.bind(that));
        });
        $(that._form).find("[data-autocomplete]").each(function() {
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
        $(that._form).on("change", ".custom-file-input", function () {
            let fileName = $(this).val().split("\\").pop();
            $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
        });
        $(that._form).find("input,textarea,select").on("keyup change force", function () {
            that._dynamicForm();

            let name = this.name;
            let keyword = $(this).val();
            name = name.replace(/[^\w+]/gi,''); // remove invalid characters

            $(that._form).find("[data-form-filter-parent="+ name +"]").each(function () {
                $(this).hide();
                if (this.dataset.formFilterKeywords.includes(keyword)) {
                    $(this).show();
                }
            });
        });
        if (this._xhr) {
            this.el.addEventListener('submit', this._formSubmit.bind(this));
        }
    }

    _formSubmit(event) {
        if (typeof event != 'undefined') {
            event.preventDefault();
        }

        const requestUrl = DomAccess.getAttribute(this._form, 'action').toLowerCase();
        const formData = FormSerializeUtil.serialize(this._form);

        this._submitButton.disabled = true;
        this._submitButton.classList.add("loading");

        this._client.post(requestUrl, formData, this._onLoaded.bind(this))
    }

    _onLoaded(response) {
        this._submitButton.disabled = false;

        response = JSON.parse(response);

        const that = this;

        response.forEach(function (responseItem) {
            if (responseItem.removeId) {
                $(responseItem.removeId).remove();
            }
            if (responseItem.type === 'success') {
                that._form.reset();

                $(that._form).find(".custom-file-input").each(function () {
                    let label = $(this).attr('placeholder');
                    $(this).siblings(".custom-file-label").removeClass("selected").html(label);
                });
                that._submitButton.classList.remove("loading");
            }
            if (responseItem.alert) {
                $(that._form).find('.moorl-form-builder-feedback').html(responseItem.alert);
                that._submitButton.classList.remove("loading");
            }
            if (responseItem.reload === true) {
                setTimeout(function () {
                    location.reload();
                }, 2000);
            }
            if (responseItem.redirectTo) {
                setTimeout(function () {
                    window.location = responseItem.redirectTo;
                }, 2000);
            }
        });
    }
}
