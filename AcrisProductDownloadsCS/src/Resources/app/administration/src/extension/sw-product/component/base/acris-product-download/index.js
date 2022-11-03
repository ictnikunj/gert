import template from './acris-product-download.html.twig';
import './acris-product-download.scss';

const { Component } = Shopware;

/**
 * @private
 * @description Component which renders an image.
 * @status ready
 * @example-type code-only
 * @component-example
 * <sw-image :item="item" isCover="true"></sw-image>
 */
Component.register('acris-product-download', {
    template,

    props: {
        mediaId: {
            type: String,
            required: true
        },

        isCover: {
            type: Boolean,
            required: false,
            default: false
        },

        isPlaceholder: {
            type: Boolean,
            required: false,
            default: false
        }
    },

    computed: {
        productImageClasses() {
            return {
                'is--placeholder': this.isPlaceholder,
                'is--cover': this.isCover
            };
        }
    }
});
