const path = require('path');

module.exports = {
  target: 'web',
  //mode: 'production',
  mode: 'development',
  entry: './src/index.js',
  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: 'jgdashboard.js',
  },
  //jsx compilation using babel
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
