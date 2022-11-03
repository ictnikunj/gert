export default class StringUtil {

  static lastChar(string) {
    string = String(string);

    if (string.length === 0) {
      return '';
    }

    return string.slice(-1);
  }

  static capitalize(string) {
    string = String(string);

    if (string.length <= 1) {
      return string.toUpperCase();
    }

    return string.charAt(0).toUpperCase() + string.slice(1);
  }

  static parseList(list, delimiter) {
    delimiter = delimiter ? String(delimiter) : ',';

    if (!list) {
      return [];
    }

    return String(list).split(delimiter).map((item) => item.trim());
  }
}
