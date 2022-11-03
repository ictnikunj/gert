export default class Download {

  constructor(data, mimeType) {
    this._blob = new Blob([data], {type: mimeType});
  }

  start(filename) {
    let a = document.createElement('a');
    a.style.display = 'none';
    document.body.appendChild(a);
    let url = window.URL.createObjectURL(this._blob);
    a.href = url;
    a.download = filename ? filename : 'download';
    a.click();
    window.URL.revokeObjectURL(url);
    a.parentNode.removeChild(a);
  }
}
