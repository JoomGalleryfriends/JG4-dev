const path = require('path');

module.exports = {
  mode: 'production',
  entry: path.resolve(__dirname, 'src/index.js'),
  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: 'uppy-uploader.js',
  },
  //transformations
  module: {
    rules: [
        {
            test: /\.jsx?/i,
            loader: 'babel-loader',
            exclude: /(.*)node_modules(.*)package\.json/
        }
    ]
},
  devtool: 'source-map',
}
