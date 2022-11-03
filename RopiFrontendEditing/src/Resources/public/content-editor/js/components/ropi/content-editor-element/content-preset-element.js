import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

export default class RopiContentPresetElement extends RopiHTMLElement {

    constructor() {
        super();
    }

    get type() {
        return (this.getAttribute('type') || '').trim();
    }

    get structure() {
        return (this.getAttribute('structure') || '');
    }

    get name() {
        return (this.getAttribute('name') || '');
    }

    get time() {
        return (this.getAttribute('time') || '');
    }

    get user() {
        return (this.getAttribute('user') || '');
    }

    get readonly() {
        return this.hasAttribute('readonly');
    }

    get formattedTime() {
        let timestamp = parseInt(this.time, 10);

        if (String(this.time).length <= 10) {
            timestamp *= 1000;
        }

        return (new Date(timestamp)).toLocaleString();
    }
}

RopiContentPresetElement._template = html`
<slot></slot>
`;

customElements.define('ropi-content-preset', RopiContentPresetElement);
