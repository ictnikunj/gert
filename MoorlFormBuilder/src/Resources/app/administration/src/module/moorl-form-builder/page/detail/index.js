const {Component, Mixin} = Shopware;
const {Criteria} = Shopware.Data;
const {mapPageErrors} = Shopware.Component.getComponentHelper();
const utils = Shopware.Utils;

import template from './index.html.twig';
import errorConfig from './error.cfg.json';
import './index.scss';

Component.register('moorl-form-builder-detail', {
    template,

    inject: [
        'repositoryFactory',
        'localeHelper'
    ],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder'),
        Mixin.getByName('discard-detail-page-changes')('form')
    ],

    shortcuts: {
        'SYSTEMKEY+S': 'onSave',
        ESCAPE: 'onAbortButtonClick'
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    data() {
        return {
            form: null,
            formElement: null,
            isLoading: true,
            processSuccess: false,
            repository: null,
            searchTerm: '',
            mediaEntity: null,
            showPicker: false,
            showUploadField: false,
            suggestedItems: [],
            isLoadingSuggestions: false,
            pickerClasses: {},
            uploadTagMedia: utils.createId(),
            customFieldSets: [],
            manufacturers: null,
            manufacturerIds: [],
            selectedProperty: null,
            mediaFolders: null,
            locale: null,
            showConfirmModal: false,
            onConfirm: null
        };
    },

    computed: {
        ...mapPageErrors(errorConfig),

        defaultCriteria() {
            const criteria = new Criteria();
            criteria.addAssociation('products.options.group');
            return criteria;
        },

        searchCriteria() {
            const criteria = new Criteria(1, 30);
            criteria.addAssociation('options.group');
            return criteria;
        },

        searchContext() {
            return {
                ...Shopware.Context.api,
                inheritance: true
            };
        },

        mailTemplateCriteria() {
            const criteria = new Criteria();
            criteria.addAssociation('mailTemplateType');
            criteria.addFilter(Criteria.equals('mailTemplateType.technicalName', 'moorl_form_builder_cms'));
            return criteria;
        },

        formRepository() {
            return this.repositoryFactory.create('moorl_form');
        },

        mediaRepository() {
            return this.repositoryFactory.create('media');
        },

        customFieldRepository() {
            return this.repositoryFactory.create('custom_field_set');
        },

        languageRepository() {
            return this.repositoryFactory.create('language');
        },

        defaultBehaviour() {
            return [
                {
                    'icon': 'default-device-mobile',
                    'breakpoint': 'base'
                },
                {
                    'icon': 'default-device-mobile',
                    'breakpoint': 'sm'
                },
                {
                    'icon': 'default-device-tablet',
                    'breakpoint': 'md'
                },
                {
                    'icon': 'default-device-tablet',
                    'breakpoint': 'lg'
                },
                {
                    'icon': 'default-device-desktop',
                    'breakpoint': 'xl'
                },
            ];
        }
    },

    created() {
        this.onChangeLanguage();
        this.getForm();
    },

    methods: {
        getFormElementCss(formElementItem) {
            if (formElementItem.conditions.length !== 0) {
                return {
                    'border-bottom': '1px dotted'
                }
            }
        },

        getTechnicalNames() {
            return this.form.data.filter(({name}) => {
                return !(!name || name.length === 0);
            }).map(({name}) => {
                return name;
            });
        },

        getNestedFormData() {
            let nest = 0;
            for (let i = 0; i < this.form.data.length; i++) {
                if (this.form.data[i].type.indexOf("close") !== -1) {
                    nest--;
                }
                this.form.data[i].nest = Number(nest);
                if (this.form.data[i].type.indexOf("open") !== -1) {
                    nest++;
                }
            }
            return this.form.data;
        },

        getEmptyFormElement() {
            let id = utils.createId();

            return {
                'id': id,
                'type': 'text',
                'value': '',
                'name': id.substring(0, 6),
                'required': null,
                'pseudo': null,
                'html': '',
                'label': {
                    'en-GB': '',
                    'de-DE': ''
                },
                'prepend': {
                    'en-GB': '',
                    'de-DE': ''
                },
                'append': {
                    'en-GB': '',
                    'de-DE': ''
                },
                'placeholder': {
                    'en-GB': '',
                    'de-DE': ''
                },
                'defaultValue': {
                    'en-GB': '',
                    'de-DE': ''
                },
                'tooltip': {
                    'en-GB': '',
                    'de-DE': ''
                },
                'numberMin': '0',
                'numberMax': '100',
                'numberStep': '0.1',
                'dateMin': '+3 days',
                'dateMax': '+6 months',
                'dateStep': '1',
                'dateExclude': [],
                'gridSizeLg': '12',
                'behaviour': {
                    'base': {
                        'visible': true,
                        'width': 12,
                        'order': 0,
                    },
                    'sm': {
                        'visible': true,
                        'width': -1,
                        'order': 0,
                    },
                    'md': {
                        'visible': true,
                        'width': -1,
                        'order': 0,
                    },
                    'lg': {
                        'visible': true,
                        'width': -1,
                        'order': 0,
                    },
                    'xl': {
                        'visible': true,
                        'width': -1,
                        'order': 0,
                    },
                },
                'cssClass': null,
                'cssId': null,
                'lineBreak': true,
                "htmlWrapper": '<div class="mb-2">{{ element }}</div>',
                'hasEmailReceiver': null,
                'useImageSelection': null,
                'isEntitySelect': null,
                'entitySelect': {
                    'relatedEntity': 'country',
                    'labelProperty': 'name',
                    'valueProperty': 'id',
                },
                'options': [
                    {
                        id: utils.createId(),
                        value: 'option1',
                        label: {
                            'en-GB': '',
                            'de-DE': ''
                        },
                        useTrans: null,
                        emailReceiver: null,
                        mediaId: null,
                        customField1: {
                            'en-GB': '',
                            'de-DE': ''
                        },
                        customField2: {
                            'en-GB': '',
                            'de-DE': ''
                        },
                        customField3: {
                            'en-GB': '',
                            'de-DE': ''
                        }
                    },
                    {
                        id: utils.createId(),
                        value: 'option2',
                        label: {
                            'en-GB': '',
                            'de-DE': ''
                        },
                        useTrans: true,
                        emailReceiver: null,
                        mediaId: null,
                        customField1: {
                            'en-GB': '',
                            'de-DE': ''
                        },
                        customField2: {
                            'en-GB': '',
                            'de-DE': ''
                        },
                        customField3: {
                            'en-GB': '',
                            'de-DE': ''
                        }
                    }
                ],
                'mapping': null,
                'autocomplete': {
                    relatedEntity: null,
                    property: null
                },
                'conditions': [],
                'mediaFileExtensions': 'zip,rar,psd,docx'
            };
        },

        loadCustomFieldSets() {
            if (this.form && this.form.relatedEntity) {
                const criteria = new Criteria(1, 100);

                criteria.addFilter(Criteria.equals('relations.entityName', this.form.relatedEntity));
                criteria.addAssociation('customFields')
                    .addSorting(Criteria.sort('config.customFieldPosition', 'ASC', true));

                this.customFieldRepository
                    .search(criteria, Shopware.Context.api)
                    .then((searchResult) => {
                        this.customFieldSets = searchResult;
                        this.isLoading = false;
                    });
            } else {
                this.isLoading = false;
            }
        },

        getForm() {
            this.formElement = null;
            this.form = null;
            this.formRepository
                .get(this.$route.params.id, this.searchContext, this.defaultCriteria)
                .then((entity) => {
                    entity = this.sanitizeForm(entity);
                    this.form = entity;
                    this.loadCustomFieldSets();
                });
        },

        sanitizeTechnicalName(value) {
            return value ? value.replace(/[^\w+]/gi,'') : value;
        },

        sanitizeForm(form) {
            // New Form?
            if (typeof form.type != 'string') {
                form.type = 'cms';
                form.name = 'New Form';
            }
            if (!Array.isArray(form.redirectConditions)) {
                form.redirectConditions = [];
            }
            form.action = this.sanitizeTechnicalName(form.action);
            form.label = this.sanitizeLocalValue(form.label);
            form.submitText = this.sanitizeLocalValue(form.submitText);
            form.successMessage = this.sanitizeLocalValue(form.successMessage);
            form.data = this.sanitizeFormData(form.data);
            return form;
        },

        sanitizeLocalValue(localValue) {
            if (Array.isArray(localValue)) {
                localValue = Object.assign({
                    'en-GB': "",
                    'de-DE': ""
                });
            } else if (typeof localValue != 'object' || !localValue) {
                localValue = Object.assign({
                    'en-GB': localValue,
                    'de-DE': localValue
                });
            }
            return localValue;
        },

        sanitizeBehaviour({behaviour, gridSizeLg}) {
            if (typeof behaviour != 'object' || !behaviour) {
                return {
                    'base': {
                        'visible': true,
                        'width': 0,
                        'order': 0
                    },
                    'sm': {
                        'visible': true,
                        'width': 0,
                        'order': 0
                    },
                    'md': {
                        'visible': true,
                        'width': 0,
                        'order': 0
                    },
                    'lg': {
                        'visible': true,
                        'width': parseInt(gridSizeLg),
                        'order': 0
                    },
                    'xl': {
                        'visible': true,
                        'width': parseInt(gridSizeLg),
                        'order': 0
                    },
                };
            } else {
                return behaviour;
            }
        },

        placeLocalValue(value, oldLocale, newLocale) {
            if (typeof value[oldLocale] != 'undefined' && value[oldLocale] != null && value[oldLocale].length && (typeof value[newLocale] == 'undefined' || !value[newLocale].length)) {
                value[newLocale] = value[oldLocale];
            }
            return value;
        },

        sanitizeFormData(data) {
            if (!data || Object.keys(data).length === 0) {
                return [];
            }

            const _that = this;

            let sanitizedData = [];

            data.forEach(function (item) {
                item.name = _that.sanitizeTechnicalName(item.name);
                item.label = _that.sanitizeLocalValue(item.label);
                item.prepend = _that.sanitizeLocalValue(item.prepend);
                item.append = _that.sanitizeLocalValue(item.append);
                item.placeholder = _that.sanitizeLocalValue(item.placeholder);
                item.defaultValue = _that.sanitizeLocalValue(item.defaultValue);
                item.tooltip = _that.sanitizeLocalValue(item.tooltip);
                item.behaviour = _that.sanitizeBehaviour(item);

                item.options.forEach(function (option) {
                    option.value = _that.sanitizeTechnicalName(option.value);
                    option.label = _that.sanitizeLocalValue(option.label);
                    option.customField1 = _that.sanitizeLocalValue(option.customField1);
                    option.customField2 = _that.sanitizeLocalValue(option.customField2);
                    option.customField3 = _that.sanitizeLocalValue(option.customField3);
                });
                sanitizedData.push(Object.assign({}, _that.getEmptyFormElement(), item));
            });

            return sanitizedData;
        },

        prepareSave() {
            this.form.maxFileSize = parseInt(this.form.maxFileSize);
        },

        onClickSave() {
            this.prepareSave();
            this.isLoading = true;
            this.formRepository
                .save(this.form, Shopware.Context.api)
                .then(() => {
                    this.getForm();
                    this.processSuccess = true;
                }).catch((exception) => {
                this.isLoading = false;
                this.createNotificationError({
                    title: this.$t('moorl-form-builder.detail.errorTitle'),
                    message: exception
                });
            });
        },

        saveFinish() {
            this.processSuccess = false;
        },

        addFormElement() {
            this.formElement = null;
            const newElement = this.getEmptyFormElement();
            this.form.data.push(newElement);
            this.editFormElement(newElement);
        },

        editFormElement(element) {
            if (typeof element == 'string') {
                this.formElement = this.form.data.find(o => o.name === element);
            } else {
                this.formElement = element;
            }

            this.$forceUpdate();
        },

        removeFormElement(element) {
            this.onConfirm = () => {
                if (this.formElement && this.formElement.id === element.id) {
                    this.formElement = null;
                }
                this.form.data = this.form.data.filter(x => x.id !== element.id);
                this.$forceUpdate();
            }

            this.openConfirmModal();
        },

        openConfirmModal() {
            this.showConfirmModal = true;
        },
        onConfirmModal() {
            this.onConfirm();
            this.onConfirm = null;

            this.onCloseModal();
        },
        onCloseModal() {
            this.showConfirmModal = false;
        },

        duplicateFormElement(element) {
            const newIndex = this.form.data.findIndex(x => x.id === element.id) + 1;
            const newElement = JSON.parse(JSON.stringify(element));
            newElement.id = utils.createId();
            newElement.name = newElement.id.substring(0, 6);

            this.form.data.splice(newIndex, 0, newElement);
            this.editFormElement(newElement);
        },

        getFormElementItemClass(formElementItemId) {
            return (this.formElement && formElementItemId == this.formElement.id ? 'active' : '');
        },

        onFormElementDragSort(dragData, dropData) {
            const remainingItems = this.form.data.filter(x => x.id !== dragData.id);
            const newIndex = this.form.data.findIndex(x => x.id === dropData.id);
            const orderedItems = [
                ...remainingItems.slice(0, newIndex),
                dragData,
                ...remainingItems.slice(newIndex)
            ];
            this.form.data = orderedItems;
            this.$forceUpdate();
        },

        newFormElementOption() {
            this.formElement.options.push({
                id: utils.createId(),
                value: 'option_x',
                label: {},
                useTrans: null
            });
        },

        removeFormElementOption(element) {
            if (this.formElement.options.length > 1) {
                this.formElement.options = this.formElement.options.filter(x => x.id !== element.id);
            } else {
                console.log("You need atleast one option.");
            }
        },

        newFormElementCondition() {
            this.formElement.conditions.push({
                id: utils.createId(),
                active: null,
                value: 'value',
                name: 'name',
                type: 'is'
            });
        },

        removeFormElementCondition(element) {
            if (this.formElement.conditions.length !== 0) {
                this.formElement.conditions = this.formElement.conditions.filter(x => x.id !== element.id);
            }
        },

        newFormRedirectCondition() {
            this.form.redirectConditions.push({
                id: utils.createId(),
                active: null,
                value: 'value',
                name: 'name',
                type: 'is'
            });
        },

        removeFormRedirectCondition(element) {
            if (this.form.redirectConditions.length !== 0) {
                this.form.redirectConditions = this.form.redirectConditions.filter(x => x.id !== element.id);
            }
        },

        configureDefaults() {
            if ([
                'customerAccountOverview',
                'customerAccountProfile',
                'customerAccountOrder',
                'customerAccountPaymentMethod'
            ].indexOf(this.form.type) !== -1) {
                this.form.insertDatabase = true;
                this.form.relatedEntity = 'customer';
            } else if (['cartLineItem'].indexOf(this.form.type) !== -1) {
                this.form.insertDatabase = false;
                this.form.relatedEntity = 'order_line_item';
            } else if (['productRequest'].indexOf(this.form.type) !== -1) {
                this.form.insertDatabase = false;
                this.form.relatedEntity = 'product';
            } else if (['cartExtend'].indexOf(this.form.type) !== -1) {
                this.form.insertDatabase = false;
                this.form.relatedEntity = 'order';
            } else if (['customerRegister'].indexOf(this.form.type) !== -1) {
                this.form.insertDatabase = false;
                this.form.relatedEntity = 'customer';
            }
        },

        getEntityOptions() {
            const storeOptions = [{
                name: "",
            }];

            const definitionRegistry = Shopware.EntityDefinition.getDefinitionRegistry();

            definitionRegistry.forEach(function (value, key, map) {
                storeOptions.push({
                    name: `${key}`
                });
            });

            return storeOptions;
        },

        getVariableOptions(entity) {
            const storeOptions = [{
                name: "",
                type: null,
            }];

            if (entity) {
                const entityDefinition = Shopware.EntityDefinition.get(entity).properties;

                Object.entries(entityDefinition).forEach(([property, value]) => {
                    if (['uuid', 'text', 'string', 'json_object', 'date', 'boolean', 'int'].indexOf(value.type) !== -1) {
                        if (property === 'customFields') {
                            console.log('entity has customFields');
                            this.customFieldSets.forEach(function (customFieldSet) {
                                customFieldSet.customFields.forEach(function (customField) {
                                    storeOptions.push({
                                        name: `${property}.${customField.name}`,
                                        type: `${customField.type}`
                                    });
                                });
                            });
                        } else {
                            storeOptions.push({
                                name: `${property}`,
                                type: `${value.type}`
                            });
                        }
                   }
                });
            }

            return storeOptions;
        },

        onChangeLanguage() {
            let oldLocale = this.locale ? this.locale.code : null;

            const criteria = new Criteria();
            criteria.addAssociation('locale');

            this.languageRepository
                .get(Shopware.Context.api.languageId, Shopware.Context.api, criteria)
                .then((entity) => {
                    this.locale = entity.locale;
                    if (this.form) {
                        this.form = this.localeFormData(this.form, oldLocale);
                    }
                });
        },

        localeFormData(form, oldLocale)
        {
            const _that = this;
            if (!form) {
                return [];
            }
            if (!oldLocale) {
                return form;
            }
            let newLocale = this.locale.code;

            form.label = _that.placeLocalValue(form.label, oldLocale, newLocale);
            form.submitText = _that.placeLocalValue(form.submitText, oldLocale, newLocale);
            form.successMessage = _that.placeLocalValue(form.successMessage, oldLocale, newLocale);

            if (!form.data || Object.keys(form.data).length === 0) {
                return form;
            }

            let sanitizedData = [];
            form.data.forEach(function (item) {
                item.label = _that.placeLocalValue(item.label, oldLocale, newLocale);
                item.prepend = _that.placeLocalValue(item.prepend, oldLocale, newLocale);
                item.append = _that.placeLocalValue(item.append, oldLocale, newLocale);
                item.placeholder = _that.placeLocalValue(item.placeholder, oldLocale, newLocale);
                item.defaultValue = _that.placeLocalValue(item.defaultValue, oldLocale, newLocale);
                item.tooltip = _that.placeLocalValue(item.tooltip, oldLocale, newLocale);

                item.options.forEach(function (option) {
                    option.label = _that.placeLocalValue(option.label, oldLocale, newLocale);
                    option.customField1 = _that.placeLocalValue(option.customField1, oldLocale, newLocale);
                    option.customField2 = _that.placeLocalValue(option.customField2, oldLocale, newLocale);
                    option.customField3 = _that.placeLocalValue(option.customField3, oldLocale, newLocale);
                });
                sanitizedData.push(Object.assign({}, item));
            });
            form.data = sanitizedData;

            return form;
        },
        openMediaSidebar() {
            this.$refs.mediaSidebarItem.openContent();
        },
        setMediaItem({targetId}, optionItem) {
            console.log('setMediaItem');
            console.log(targetId);
            console.log(optionItem);
            this.mediaRepository.get(targetId, Shopware.Context.api).then((updatedMedia) => {
                if (optionItem && updatedMedia.id) {
                    optionItem.mediaId = targetId;
                    optionItem.media = updatedMedia;
                }
                this.$forceUpdate();
            });
        },
        onDropMedia(dragData, optionItem) {
            console.log('onDropMedia');
            console.log(dragData);
            console.log(optionItem);
            this.setMediaItem({targetId: dragData.id}, optionItem);
        },
        setMediaFromSidebar(mediaEntity, optionItem) {
            console.log('setMediaFromSidebar');
            console.log(mediaEntity);
            console.log(optionItem);
            if (optionItem && mediaEntity.id) {
                optionItem.media = mediaEntity;
                optionItem.mediaId = mediaEntity.id;
            }
        },
        onUnlinkMedia(optionItem) {
            console.log('onUnlinkMedia');
            if (optionItem) {
                optionItem.media = null;
                optionItem.mediaId = null;
            }
        }
    }
});
