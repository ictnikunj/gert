import template from './sw-plugin-list.html.twig';
import './sw-plugin-list.scss';

const { Component } = Shopware;

Component.override('sw-plugin-list', {
    template,

    computed: {
        pluginColumns() {
            return this.$super('pluginColumns').reduce((accumulator, column) => {
                if (column.property === 'label') {
                    column.multiLine = true;
                }
                accumulator.push(column);

                return accumulator;
            }, []);
        }
    }
});
