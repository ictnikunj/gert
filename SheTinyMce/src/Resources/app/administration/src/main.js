const {Component} = Shopware;
import template from './extension/sw-tinymce.html.twig';
import './sw-tinymce.scss';
import './service/sheApiTestService';
import './component/she-api-test-button';

import localeDE from './snippet/de_DE.json';
import localeEN from './snippet/en_GB.json';
Shopware.Locale.extend('de-DE', localeDE);
Shopware.Locale.extend('en-GB', localeEN);

Component.override('sw-text-editor', {
  template,

  inject: ['systemConfigApiService'],

  data() {
    return {
      mediaModalIsOpen: false,
      domain: 'SheTinyMce',
    };
  },

  methods: {
    setupEditor() {
      if (this.isInlineEdit) {
        this.mountedComponent();
        return;
      }

      this.readAll().then((values) => {
        window.tinymceConfig = this.mapValues(values);
        this.loadTiny();
      });
    },

    onCloseMediaModal() {
      this.mediaModalIsOpen = false;
    },

    onMediaSelectionChange(mediaItems) {
      mediaItems.forEach((item) => {
        if (this.filePickerMeta.filetype === 'file') {
          const text = `${item.fileName}.${item.fileExtension}`;
          this.filePickerCallback(item.url, {
            title: item.translated.title,
            text: text,
          });
        } else if (this.filePickerMeta.filetype === 'image') {
          this.filePickerCallback(item.url, {alt: item.translated.alt});
        } else {
          this.filePickerCallback(item.url);
        }
      });
    },

    onChange(e) {
      this.value = e.target.getContent();
      this.emitHtmlContent(e.target.getContent());
    },

    readAll() {
      return this.systemConfigApiService.getValues(
          this.domain,
          this.selectedSalesChannelId,
      );
    },

    loadTiny() {
      if (!window.tinymce) {
        return;
      }

      const lang =
          Shopware.
              Application.
              getContainer('factory').
              locale.
              getLastKnownLocale();

      const contentCss = window.tinymceConfig.contentcss;
      const styleFormats = window.tinymceConfig.styleformats;
      const templates = window.tinymceConfig.templates;
      const relativeUrls = !!window.tinymceConfig.relativeUrls;
      const convertUrls = !!window.tinymceConfig.convertUrls;
      const removeScriptHost = !!window.tinymceConfig.removeScriptHost;

      window.tinymce.init(this.getTinyMceConfig(
          lang,
          contentCss,
          styleFormats,
          templates,
          relativeUrls,
          convertUrls,
          removeScriptHost,
      ));
    },

    getTinyMceConfig: function(
        lang,
        contentCss,
        styleFormats,
        templates,
        relativeUrls,
        convertUrls,
        removeScriptHost,
    ) {
      const me = this;
      const plugins = window.tinymceConfig.enablePro ?
                'iconfonts paste code print preview fullpage powerpaste casechange importcss' +
                ' searchreplace autolink autosave save directionality' +
                ' advcode code visualblocks visualchars fullscreen image link media mediaembed' +
                ' template codesample table charmap hr pagebreak nonbreaking anchor' +
                ' toc insertdatetime advlist lists checklist wordcount tinymcespellchecker' +
                ' a11ychecker imagetools textpattern noneditable help formatpainter permanentpen' +
                ' pageembed charmap tinycomments mentions quickbars linkchecker emoticons advtable' :
                'iconfonts print preview paste importcss searchreplace autolink' +
                ' autosave save directionality code visualblocks visualchars' +
                ' fullscreen image link media template codesample table charmap' +
                ' hr pagebreak nonbreaking anchor toc insertdatetime advlist' +
                ' lists wordcount imagetools textpattern noneditable help charmap' +
                ' quickbars emoticons';
      return {
        target: this.$refs.textArea,
        language: lang.substring(0, 2),
        skin: window.tinymceConfig.skin || 'oxide',
        height: window.tinymceConfig.height || 300,
        plugins: plugins,
        menubar: 'file edit view insert format tools table help',
        toolbar: 'undo redo | bold italic underline strikethrough |' +
                    ' fontselect fontsizeselect formatselect |' +
                    ' alignleft aligncenter alignright alignjustify |' +
                    ' outdent indent |  numlist bullist |' +
                    ' forecolor backcolor removeformat | pagebreak |' +
                    ' charmap emoticons | fullscreen  preview save print |' +
                    ' insertfile image media template link anchor codesample |' +
                    ' ltr rtl',
        style_formats: styleFormats ? JSON.parse(styleFormats) : [],
        toolbar_sticky: true,
        image_advtab: true,
        content_css: contentCss ? contentCss.split(/\n/) : [],
        image_class_list: [{
          title: 'None', value: '',
        }, {
          title: 'Some class', value: 'class-name',
        }],
        browser_spellcheck: !!window.tinymceConfig.spellcheck,
        importcss_append: true,
        autosave_ask_before_unload: false,
        relative_urls: relativeUrls,
        convert_urls: convertUrls,
        remove_script_host: removeScriptHost,
        tinycomments_mode: 'embedded',
        file_picker_callback: function(callback, value, meta) {
          /* Provide file and text for the link dialog */
          me.mediaModalIsOpen = true;
          me.filePickerCallback = callback;
          me.filePickerMeta = meta;
        },
        templates: templates ? JSON.parse(templates) : [
          {
            title: 'New Table',
            description: 'creates a new table',
            content: '',
          }, {
            title: 'Starting my story',
            description: 'A cure for writers block',
            content: 'Once upon a time...',
          },
        ],
        template_cdate_format: '[Date Created (CDATE): %m/%d/%Y : %H:%M:%S]',
        template_mdate_format: '[Date Modified (MDATE): %m/%d/%Y : %H:%M:%S]',
        image_caption: true,
        quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 blockquote quickimage quicktable',
        noneditable_noneditable_class: 'mceNonEditable',
        toolbar_drawer: 'sliding',
        contextmenu: window.tinymceConfig.spellcheck ? false : 'link image imagetools table',
        init_instance_callback: function(editor) {
          editor.on('Change', me.onChange);
        },
        setup: (editor) => {
          if (typeof $ === 'function') {
            editor.on('blur', () => {
              $('.tox-pop').remove();
            });
          }
        },
        extended_valid_elements: window.tinymceConfig.disableCleanup ? '*[*]' : 'script[src|async|defer|type|charset|crossorigin]',
        cleanup: !window.tinymceConfig.disableCleanup,
        verify_html: !window.tinymceConfig.disableCleanup,
      };
    },

    mapValues: function(values) {
      const config = {};
      Object.keys(values).forEach((key) => {
        const newKey = key.replace('SheTinyMce.config.', '');
        config[newKey] = values[key];
      });
      return config;
    },
  },

  watch: {
    value: {
      handler() {
        if (this.$refs.textArea) {
          const content = window.tinymce
              .get(this.$refs.textArea.id).getContent();

          if (this.value !== content) {
            window.tinymce.
                get(this.$refs.textArea.id).
                getBody().innerHTML = this.value;

            this.content = this.value;
            this.isEmpty = this.emptyCheck(this.content);
            this.placeholderVisible = this.isEmpty;
          }
        }
      },
    },
  },

  mounted() {
    this.setupEditor();
  },
});
