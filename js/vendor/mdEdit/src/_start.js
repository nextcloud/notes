(function (root, factory) {
    if (typeof define === 'function' && define['amd']) {
        // AMD. Register as an anonymous module.
        define(['prismjs'], factory);
    } else if (typeof exports === 'object') {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like environments that support module.exports,
        // like Node.
        module['exports'] = factory(require('prismjs'));
    } else {
        // Browser globals (root is window)
        root['mdEdit'] = factory(root['Prism']);
    }
}(this, function (Prism) {
