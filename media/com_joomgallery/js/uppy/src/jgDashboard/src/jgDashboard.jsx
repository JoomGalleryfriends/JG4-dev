import { h } from 'preact'
import memoizeOne from 'memoize-one'
import Dashboard from '@uppy/dashboard/src/Dashboard.jsx'
import DashboardUI from './components/Dashboard.jsx'

const memoize = memoizeOne.default || memoizeOne

const TAB_KEY = 9
const ESC_KEY = 27

function createPromise () {
  const o = {}
  o.promise = new Promise((resolve, reject) => {
    o.resolve = resolve
    o.reject = reject
  })
  return o
}

function defaultPickerIcon () {
  return (
    <svg aria-hidden="true" focusable="false" width="30" height="30" viewBox="0 0 30 30">
      <path d="M15 30c8.284 0 15-6.716 15-15 0-8.284-6.716-15-15-15C6.716 0 0 6.716 0 15c0 8.284 6.716 15 15 15zm4.258-12.676v6.846h-8.426v-6.846H5.204l9.82-12.364 9.82 12.364H19.26z" />
    </svg>
  )
}

/**
 * Dashboard UI with previews, metadata editing, tabs for various services and more
 */
export default class jgDashboard extends Dashboard {
  #attachRenderFunctionToTarget = (target) => {
    const plugin = this.uppy.getPlugin(target.id)
    return {
      ...target,
      icon: plugin.icon || this.opts.defaultPickerIcon,
      render: plugin.render,
    }
  }

  #isTargetSupported = (target) => {
    const plugin = this.uppy.getPlugin(target.id)
    // If the plugin does not provide a `supported` check, assume the plugin works everywhere.
    if (typeof plugin.isSupported !== 'function') {
      return true
    }
    return plugin.isSupported()
  }

  #getAcquirers = memoize((targets) => {
    return targets
      .filter(target => target.type === 'acquirer' && this.#isTargetSupported(target))
      .map(this.#attachRenderFunctionToTarget)
  })

  #getProgressIndicators = memoize((targets) => {
    return targets
      .filter(target => target.type === 'progressindicator')
      .map(this.#attachRenderFunctionToTarget)
  })

  #getEditors = memoize((targets) => {
    return targets
      .filter(target => target.type === 'editor')
      .map(this.#attachRenderFunctionToTarget)
  })

  render = (state) => {
    const pluginState = this.getPluginState()
    const { files, capabilities, allowNewUpload } = state
    const {
      newFiles,
      uploadStartedFiles,
      completeFiles,
      erroredFiles,
      inProgressFiles,
      inProgressNotPausedFiles,
      processingFiles,

      isUploadStarted,
      isAllComplete,
      isAllErrored,
      isAllPaused,
    } = this.uppy.getObjectOfFilesPerState()

    const acquirers = this.#getAcquirers(pluginState.targets)
    const progressindicators = this.#getProgressIndicators(pluginState.targets)
    const editors = this.#getEditors(pluginState.targets)

    let theme
    if (this.opts.theme === 'auto') {
      theme = capabilities.darkMode ? 'dark' : 'light'
    } else {
      theme = this.opts.theme
    }

    if (['files', 'folders', 'both'].indexOf(this.opts.fileManagerSelectionType) < 0) {
      this.opts.fileManagerSelectionType = 'files'
      // eslint-disable-next-line no-console
      console.warn(`Unsupported option for "fileManagerSelectionType". Using default of "${this.opts.fileManagerSelectionType}".`)
    }

    return DashboardUI({
      state,
      isHidden: pluginState.isHidden,
      files,
      newFiles,
      uploadStartedFiles,
      completeFiles,
      erroredFiles,
      inProgressFiles,
      inProgressNotPausedFiles,
      processingFiles,
      isUploadStarted,
      isAllComplete,
      isAllErrored,
      isAllPaused,
      totalFileCount: Object.keys(files).length,
      totalProgress: state.totalProgress,
      allowNewUpload,
      acquirers,
      theme,
      disabled: this.opts.disabled,
      disableLocalFiles: this.opts.disableLocalFiles,
      direction: this.opts.direction,
      activePickerPanel: pluginState.activePickerPanel,
      showFileEditor: pluginState.showFileEditor,
      saveFileEditor: this.saveFileEditor,
      disableInteractiveElements: this.disableInteractiveElements,
      animateOpenClose: this.opts.animateOpenClose,
      isClosing: pluginState.isClosing,
      progressindicators,
      editors,
      autoProceed: this.uppy.opts.autoProceed,
      id: this.id,
      closeModal: this.requestCloseModal,
      handleClickOutside: this.handleClickOutside,
      handleInputChange: this.handleInputChange,
      handlePaste: this.handlePaste,
      inline: this.opts.inline,
      showPanel: this.showPanel,
      hideAllPanels: this.hideAllPanels,
      i18n: this.i18n,
      i18nArray: this.i18nArray,
      uppy: this.uppy,
      note: this.opts.note,
      recoveredState: state.recoveredState,
      metaFields: pluginState.metaFields,
      resumableUploads: capabilities.resumableUploads || false,
      individualCancellation: capabilities.individualCancellation,
      isMobileDevice: capabilities.isMobileDevice,
      fileCardFor: pluginState.fileCardFor,
      toggleFileCard: this.toggleFileCard,
      toggleAddFilesPanel: this.toggleAddFilesPanel,
      showAddFilesPanel: pluginState.showAddFilesPanel,
      saveFileCard: this.saveFileCard,
      openFileEditor: this.openFileEditor,
      canEditFile: this.canEditFile,
      width: this.opts.width,
      height: this.opts.height,
      showLinkToFileUploadResult: this.opts.showLinkToFileUploadResult,
      fileManagerSelectionType: this.opts.fileManagerSelectionType,
      proudlyDisplayPoweredByUppy: this.opts.proudlyDisplayPoweredByUppy,
      hideCancelButton: this.opts.hideCancelButton,
      hideRetryButton: this.opts.hideRetryButton,
      hidePauseResumeButton: this.opts.hidePauseResumeButton,
      showRemoveButtonAfterComplete: this.opts.showRemoveButtonAfterComplete,
      containerWidth: pluginState.containerWidth,
      containerHeight: pluginState.containerHeight,
      areInsidesReadyToBeVisible: pluginState.areInsidesReadyToBeVisible,
      isTargetDOMEl: this.isTargetDOMEl,
      parentElement: this.el,
      allowedFileTypes: this.uppy.opts.restrictions.allowedFileTypes,
      maxNumberOfFiles: this.uppy.opts.restrictions.maxNumberOfFiles,
      requiredMetaFields: this.uppy.opts.restrictions.requiredMetaFields,
      showSelectedFiles: this.opts.showSelectedFiles,
      showNativePhotoCameraButton: this.opts.showNativePhotoCameraButton,
      showNativeVideoCameraButton: this.opts.showNativeVideoCameraButton,
      nativeCameraFacingMode: this.opts.nativeCameraFacingMode,
      singleFileFullScreen: this.opts.singleFileFullScreen,
      handleCancelRestore: this.handleCancelRestore,
      handleRequestThumbnail: this.handleRequestThumbnail,
      handleCancelThumbnail: this.handleCancelThumbnail,
      // drag props
      isDraggingOver: pluginState.isDraggingOver,
      handleDragOver: this.handleDragOver,
      handleDragLeave: this.handleDragLeave,
      handleDrop: this.handleDrop,
    })
  }
}
