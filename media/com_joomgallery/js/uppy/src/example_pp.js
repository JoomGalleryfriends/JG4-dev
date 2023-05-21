class FileApiPostProcessor extends Plugin {
  constructor(uppy, opts) {
    super(uppy, opts);
    this.id = this.opts.id || 'FileApi';
    this.type = 'uploader';

    delete this.opts.success;
    delete this.opts.error;

  }

  setOptions (newOpts) {
    super.setOptions(newOpts);
  }

  sendFileApiRequest = async (file) => {...}

  prepareFileApiRequest = (fileIds) => {
      const promises = fileIds.map((fileId) => {
        const file = this.uppy.getFile(fileId);
        this.uppy.emit('postprocess-progress', file, {
          mode: 'indeterminate',
          message: 'Updating records',
        });

        return this.sendFileApiRequest(file).then((fileResponse) => {
          this.uppy.log(`[File Api] File ${file.id} updated sucessfully`);
          this.uppy.setFileState(fileId, { fileInfo: fileResponse });
        }).catch((error) => {
          this.uppy.setFileState(fileId, { error });
          this.uppy.log(`[File Api] Failed to update ${file.id} record`, 'error');
          this.uppy.log(error, 'error');
        });
      });


      const emitPostprocessCompleteForAll = () => {
        fileIds.forEach((fileId) => {
          const file = this.uppy.getFile(fileId);
          this.uppy.emit('postprocess-complete', file);
        })
      };


      return Promise.all(promises)
        .then(emitPostprocessCompleteForAll);
    }

  install () {
    this.uppy.addPostProcessor(this.prepareFileApiRequest);
  }

  uninstall () {
    this.uppy.removePostProcessor(this.prepareFileApiRequest);
  }
}

export default FileApiPostProcessor;