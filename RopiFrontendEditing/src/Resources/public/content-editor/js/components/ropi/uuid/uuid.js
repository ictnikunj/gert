export default class {

  static v4() {
    let seed = Date.now();

    if (window.performance && window.performance.now) {
      seed += performance.now();
    }

    let uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
      let r = (seed + Math.random() * 16) % 16 | 0;
      seed = Math.floor(seed / 16);

      return (c === 'x' ? r : r & (0x3|0x8)).toString(16);
    });

    return uuid;
  }

}
