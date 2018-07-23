const path = require('path');

module.exports = {
    entry: './web/index.js',
    mode: 'production',
    output: {
        filename: 'bundle.js',
        path: path.resolve(__dirname, 'dist')
    }
};