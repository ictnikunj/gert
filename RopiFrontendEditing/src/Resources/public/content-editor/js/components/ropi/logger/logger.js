export default new class {

  get LEVEL_NONE() {
    return 0;
  }

  get LEVEL_ERROR() {
    return 1;
  }

  get LEVEL_WARNING() {
    return 2;
  }

  get LEVEL_INFO() {
    return 3;
  }

  get LEVEL_DEBUG() {
    return 4;
  }

  get LEVEL_TRACE() {
    return 5;
  }

  get LEVEL_ALL() {
    return 6;
  }

  constructor() {
    this._level = this.LEVEL_NONE;
  }

  set level(level) {
    this._level = level;
  }

  get level() {
    return this._level;
  }

  logError() {
    this.log(this.LEVEL_ERROR, ...arguments);
  }

  logWarning() {
    this.log(this.LEVEL_WARNING, ...arguments);
  }

  logInfo() {
    this.log(this.LEVEL_INFO, ...arguments);
  }

  logDebug() {
    this.log(this.LEVEL_DEBUG, ...arguments);
  }

  logTrace() {
    this.log(this.LEVEL_TRACE, ...arguments);
  }

  log(level) {
    if (!level || level > this.level) {
      return;
    }

    let cleanArgs = Array.from(arguments);
    cleanArgs.shift();

    if (level <= this.LEVEL_WARNING) {
      console.trace(this._levelPrefix(level), ...cleanArgs);
    } else {
      console.log(this._levelPrefix(level), ...cleanArgs);
    }
  }

  _levelPrefix(level) {
    switch (level) {
      case this.LEVEL_ERROR:
        return '[ERROR]';
      case this.LEVEL_WARNING:
        return '[WARNING]';
      case this.LEVEL_INFO:
        return '[INFO]';
      case this.LEVEL_DEBUG:
        return '[DEBUG]';
      case this.LEVEL_TRACE:
        return '[TRACE]';
    }

    return '';
  }
}
