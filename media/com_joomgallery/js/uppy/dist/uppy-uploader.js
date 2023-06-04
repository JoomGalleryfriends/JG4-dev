/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/@transloadit/prettier-bytes/prettierBytes.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@transloadit/prettier-bytes/prettierBytes.js ***!
  \*******************************************************************/
/***/ ((module) => {

// Adapted from https://github.com/Flet/prettier-bytes/
// Changing 1000 bytes to 1024, so we can keep uppercase KB vs kB
// ISC License (c) Dan Flettre https://github.com/Flet/prettier-bytes/blob/master/LICENSE
module.exports = function prettierBytes (num) {
  if (typeof num !== 'number' || isNaN(num)) {
    throw new TypeError(`Expected a number, got ${typeof num}`)
  }

  const neg = num < 0
  const units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB']

  if (neg) {
    num = -num
  }

  if (num < 1) {
    return `${(neg ? '-' : '') + num} B`
  }

  const exponent = Math.min(Math.floor(Math.log(num) / Math.log(1024)), units.length - 1)
  num = Number(num / Math.pow(1024, exponent))
  const unit = units[exponent]

  if (num >= 10 || num % 1 === 0) {
    // Do not show decimals when the number is two-digit, or if the number has no
    // decimal component.
    return `${(neg ? '-' : '') + num.toFixed(0)} ${unit}`
  }
  return `${(neg ? '-' : '') + num.toFixed(1)} ${unit}`
}


/***/ }),

/***/ "./node_modules/async-sema/lib/index.js":
/*!**********************************************!*\
  !*** ./node_modules/async-sema/lib/index.js ***!
  \**********************************************/
/***/ (function(__unused_webpack_module, exports, __webpack_require__) {

"use strict";

var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", ({ value: true }));
exports.RateLimit = exports.Sema = void 0;
const events_1 = __importDefault(__webpack_require__(/*! events */ "./node_modules/events/events.js"));
function arrayMove(src, srcIndex, dst, dstIndex, len) {
    for (let j = 0; j < len; ++j) {
        dst[j + dstIndex] = src[j + srcIndex];
        src[j + srcIndex] = void 0;
    }
}
function pow2AtLeast(n) {
    n = n >>> 0;
    n = n - 1;
    n = n | (n >> 1);
    n = n | (n >> 2);
    n = n | (n >> 4);
    n = n | (n >> 8);
    n = n | (n >> 16);
    return n + 1;
}
function getCapacity(capacity) {
    return pow2AtLeast(Math.min(Math.max(16, capacity), 1073741824));
}
// Deque is based on https://github.com/petkaantonov/deque/blob/master/js/deque.js
// Released under the MIT License: https://github.com/petkaantonov/deque/blob/6ef4b6400ad3ba82853fdcc6531a38eb4f78c18c/LICENSE
class Deque {
    constructor(capacity) {
        this._capacity = getCapacity(capacity);
        this._length = 0;
        this._front = 0;
        this.arr = [];
    }
    push(item) {
        const length = this._length;
        this.checkCapacity(length + 1);
        const i = (this._front + length) & (this._capacity - 1);
        this.arr[i] = item;
        this._length = length + 1;
        return length + 1;
    }
    pop() {
        const length = this._length;
        if (length === 0) {
            return void 0;
        }
        const i = (this._front + length - 1) & (this._capacity - 1);
        const ret = this.arr[i];
        this.arr[i] = void 0;
        this._length = length - 1;
        return ret;
    }
    shift() {
        const length = this._length;
        if (length === 0) {
            return void 0;
        }
        const front = this._front;
        const ret = this.arr[front];
        this.arr[front] = void 0;
        this._front = (front + 1) & (this._capacity - 1);
        this._length = length - 1;
        return ret;
    }
    get length() {
        return this._length;
    }
    checkCapacity(size) {
        if (this._capacity < size) {
            this.resizeTo(getCapacity(this._capacity * 1.5 + 16));
        }
    }
    resizeTo(capacity) {
        const oldCapacity = this._capacity;
        this._capacity = capacity;
        const front = this._front;
        const length = this._length;
        if (front + length > oldCapacity) {
            const moveItemsCount = (front + length) & (oldCapacity - 1);
            arrayMove(this.arr, 0, this.arr, oldCapacity, moveItemsCount);
        }
    }
}
class ReleaseEmitter extends events_1.default {
}
function isFn(x) {
    return typeof x === 'function';
}
function defaultInit() {
    return '1';
}
class Sema {
    constructor(nr, { initFn = defaultInit, pauseFn, resumeFn, capacity = 10, } = {}) {
        if (isFn(pauseFn) !== isFn(resumeFn)) {
            throw new Error('pauseFn and resumeFn must be both set for pausing');
        }
        this.nrTokens = nr;
        this.free = new Deque(nr);
        this.waiting = new Deque(capacity);
        this.releaseEmitter = new ReleaseEmitter();
        this.noTokens = initFn === defaultInit;
        this.pauseFn = pauseFn;
        this.resumeFn = resumeFn;
        this.paused = false;
        this.releaseEmitter.on('release', (token) => {
            const p = this.waiting.shift();
            if (p) {
                p.resolve(token);
            }
            else {
                if (this.resumeFn && this.paused) {
                    this.paused = false;
                    this.resumeFn();
                }
                this.free.push(token);
            }
        });
        for (let i = 0; i < nr; i++) {
            this.free.push(initFn());
        }
    }
    tryAcquire() {
        return this.free.pop();
    }
    async acquire() {
        let token = this.tryAcquire();
        if (token !== void 0) {
            return token;
        }
        return new Promise((resolve, reject) => {
            if (this.pauseFn && !this.paused) {
                this.paused = true;
                this.pauseFn();
            }
            this.waiting.push({ resolve, reject });
        });
    }
    release(token) {
        this.releaseEmitter.emit('release', this.noTokens ? '1' : token);
    }
    drain() {
        const a = new Array(this.nrTokens);
        for (let i = 0; i < this.nrTokens; i++) {
            a[i] = this.acquire();
        }
        return Promise.all(a);
    }
    nrWaiting() {
        return this.waiting.length;
    }
}
exports.Sema = Sema;
function RateLimit(rps, { timeUnit = 1000, uniformDistribution = false, } = {}) {
    const sema = new Sema(uniformDistribution ? 1 : rps);
    const delay = uniformDistribution ? timeUnit / rps : timeUnit;
    return async function rl() {
        await sema.acquire();
        setTimeout(() => sema.release(), delay);
    };
}
exports.RateLimit = RateLimit;


/***/ }),

/***/ "./node_modules/events/events.js":
/*!***************************************!*\
  !*** ./node_modules/events/events.js ***!
  \***************************************/
/***/ ((module) => {

"use strict";
// Copyright Joyent, Inc. and other Node contributors.
//
// Permission is hereby granted, free of charge, to any person obtaining a
// copy of this software and associated documentation files (the
// "Software"), to deal in the Software without restriction, including
// without limitation the rights to use, copy, modify, merge, publish,
// distribute, sublicense, and/or sell copies of the Software, and to permit
// persons to whom the Software is furnished to do so, subject to the
// following conditions:
//
// The above copyright notice and this permission notice shall be included
// in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN
// NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
// DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
// OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE
// USE OR OTHER DEALINGS IN THE SOFTWARE.



var R = typeof Reflect === 'object' ? Reflect : null
var ReflectApply = R && typeof R.apply === 'function'
  ? R.apply
  : function ReflectApply(target, receiver, args) {
    return Function.prototype.apply.call(target, receiver, args);
  }

var ReflectOwnKeys
if (R && typeof R.ownKeys === 'function') {
  ReflectOwnKeys = R.ownKeys
} else if (Object.getOwnPropertySymbols) {
  ReflectOwnKeys = function ReflectOwnKeys(target) {
    return Object.getOwnPropertyNames(target)
      .concat(Object.getOwnPropertySymbols(target));
  };
} else {
  ReflectOwnKeys = function ReflectOwnKeys(target) {
    return Object.getOwnPropertyNames(target);
  };
}

function ProcessEmitWarning(warning) {
  if (console && console.warn) console.warn(warning);
}

var NumberIsNaN = Number.isNaN || function NumberIsNaN(value) {
  return value !== value;
}

function EventEmitter() {
  EventEmitter.init.call(this);
}
module.exports = EventEmitter;
module.exports.once = once;

// Backwards-compat with node 0.10.x
EventEmitter.EventEmitter = EventEmitter;

EventEmitter.prototype._events = undefined;
EventEmitter.prototype._eventsCount = 0;
EventEmitter.prototype._maxListeners = undefined;

// By default EventEmitters will print a warning if more than 10 listeners are
// added to it. This is a useful default which helps finding memory leaks.
var defaultMaxListeners = 10;

function checkListener(listener) {
  if (typeof listener !== 'function') {
    throw new TypeError('The "listener" argument must be of type Function. Received type ' + typeof listener);
  }
}

Object.defineProperty(EventEmitter, 'defaultMaxListeners', {
  enumerable: true,
  get: function() {
    return defaultMaxListeners;
  },
  set: function(arg) {
    if (typeof arg !== 'number' || arg < 0 || NumberIsNaN(arg)) {
      throw new RangeError('The value of "defaultMaxListeners" is out of range. It must be a non-negative number. Received ' + arg + '.');
    }
    defaultMaxListeners = arg;
  }
});

EventEmitter.init = function() {

  if (this._events === undefined ||
      this._events === Object.getPrototypeOf(this)._events) {
    this._events = Object.create(null);
    this._eventsCount = 0;
  }

  this._maxListeners = this._maxListeners || undefined;
};

// Obviously not all Emitters should be limited to 10. This function allows
// that to be increased. Set to zero for unlimited.
EventEmitter.prototype.setMaxListeners = function setMaxListeners(n) {
  if (typeof n !== 'number' || n < 0 || NumberIsNaN(n)) {
    throw new RangeError('The value of "n" is out of range. It must be a non-negative number. Received ' + n + '.');
  }
  this._maxListeners = n;
  return this;
};

function _getMaxListeners(that) {
  if (that._maxListeners === undefined)
    return EventEmitter.defaultMaxListeners;
  return that._maxListeners;
}

EventEmitter.prototype.getMaxListeners = function getMaxListeners() {
  return _getMaxListeners(this);
};

EventEmitter.prototype.emit = function emit(type) {
  var args = [];
  for (var i = 1; i < arguments.length; i++) args.push(arguments[i]);
  var doError = (type === 'error');

  var events = this._events;
  if (events !== undefined)
    doError = (doError && events.error === undefined);
  else if (!doError)
    return false;

  // If there is no 'error' event listener then throw.
  if (doError) {
    var er;
    if (args.length > 0)
      er = args[0];
    if (er instanceof Error) {
      // Note: The comments on the `throw` lines are intentional, they show
      // up in Node's output if this results in an unhandled exception.
      throw er; // Unhandled 'error' event
    }
    // At least give some kind of context to the user
    var err = new Error('Unhandled error.' + (er ? ' (' + er.message + ')' : ''));
    err.context = er;
    throw err; // Unhandled 'error' event
  }

  var handler = events[type];

  if (handler === undefined)
    return false;

  if (typeof handler === 'function') {
    ReflectApply(handler, this, args);
  } else {
    var len = handler.length;
    var listeners = arrayClone(handler, len);
    for (var i = 0; i < len; ++i)
      ReflectApply(listeners[i], this, args);
  }

  return true;
};

function _addListener(target, type, listener, prepend) {
  var m;
  var events;
  var existing;

  checkListener(listener);

  events = target._events;
  if (events === undefined) {
    events = target._events = Object.create(null);
    target._eventsCount = 0;
  } else {
    // To avoid recursion in the case that type === "newListener"! Before
    // adding it to the listeners, first emit "newListener".
    if (events.newListener !== undefined) {
      target.emit('newListener', type,
                  listener.listener ? listener.listener : listener);

      // Re-assign `events` because a newListener handler could have caused the
      // this._events to be assigned to a new object
      events = target._events;
    }
    existing = events[type];
  }

  if (existing === undefined) {
    // Optimize the case of one listener. Don't need the extra array object.
    existing = events[type] = listener;
    ++target._eventsCount;
  } else {
    if (typeof existing === 'function') {
      // Adding the second element, need to change to array.
      existing = events[type] =
        prepend ? [listener, existing] : [existing, listener];
      // If we've already got an array, just append.
    } else if (prepend) {
      existing.unshift(listener);
    } else {
      existing.push(listener);
    }

    // Check for listener leak
    m = _getMaxListeners(target);
    if (m > 0 && existing.length > m && !existing.warned) {
      existing.warned = true;
      // No error code for this since it is a Warning
      // eslint-disable-next-line no-restricted-syntax
      var w = new Error('Possible EventEmitter memory leak detected. ' +
                          existing.length + ' ' + String(type) + ' listeners ' +
                          'added. Use emitter.setMaxListeners() to ' +
                          'increase limit');
      w.name = 'MaxListenersExceededWarning';
      w.emitter = target;
      w.type = type;
      w.count = existing.length;
      ProcessEmitWarning(w);
    }
  }

  return target;
}

EventEmitter.prototype.addListener = function addListener(type, listener) {
  return _addListener(this, type, listener, false);
};

EventEmitter.prototype.on = EventEmitter.prototype.addListener;

EventEmitter.prototype.prependListener =
    function prependListener(type, listener) {
      return _addListener(this, type, listener, true);
    };

function onceWrapper() {
  if (!this.fired) {
    this.target.removeListener(this.type, this.wrapFn);
    this.fired = true;
    if (arguments.length === 0)
      return this.listener.call(this.target);
    return this.listener.apply(this.target, arguments);
  }
}

function _onceWrap(target, type, listener) {
  var state = { fired: false, wrapFn: undefined, target: target, type: type, listener: listener };
  var wrapped = onceWrapper.bind(state);
  wrapped.listener = listener;
  state.wrapFn = wrapped;
  return wrapped;
}

EventEmitter.prototype.once = function once(type, listener) {
  checkListener(listener);
  this.on(type, _onceWrap(this, type, listener));
  return this;
};

EventEmitter.prototype.prependOnceListener =
    function prependOnceListener(type, listener) {
      checkListener(listener);
      this.prependListener(type, _onceWrap(this, type, listener));
      return this;
    };

// Emits a 'removeListener' event if and only if the listener was removed.
EventEmitter.prototype.removeListener =
    function removeListener(type, listener) {
      var list, events, position, i, originalListener;

      checkListener(listener);

      events = this._events;
      if (events === undefined)
        return this;

      list = events[type];
      if (list === undefined)
        return this;

      if (list === listener || list.listener === listener) {
        if (--this._eventsCount === 0)
          this._events = Object.create(null);
        else {
          delete events[type];
          if (events.removeListener)
            this.emit('removeListener', type, list.listener || listener);
        }
      } else if (typeof list !== 'function') {
        position = -1;

        for (i = list.length - 1; i >= 0; i--) {
          if (list[i] === listener || list[i].listener === listener) {
            originalListener = list[i].listener;
            position = i;
            break;
          }
        }

        if (position < 0)
          return this;

        if (position === 0)
          list.shift();
        else {
          spliceOne(list, position);
        }

        if (list.length === 1)
          events[type] = list[0];

        if (events.removeListener !== undefined)
          this.emit('removeListener', type, originalListener || listener);
      }

      return this;
    };

EventEmitter.prototype.off = EventEmitter.prototype.removeListener;

EventEmitter.prototype.removeAllListeners =
    function removeAllListeners(type) {
      var listeners, events, i;

      events = this._events;
      if (events === undefined)
        return this;

      // not listening for removeListener, no need to emit
      if (events.removeListener === undefined) {
        if (arguments.length === 0) {
          this._events = Object.create(null);
          this._eventsCount = 0;
        } else if (events[type] !== undefined) {
          if (--this._eventsCount === 0)
            this._events = Object.create(null);
          else
            delete events[type];
        }
        return this;
      }

      // emit removeListener for all listeners on all events
      if (arguments.length === 0) {
        var keys = Object.keys(events);
        var key;
        for (i = 0; i < keys.length; ++i) {
          key = keys[i];
          if (key === 'removeListener') continue;
          this.removeAllListeners(key);
        }
        this.removeAllListeners('removeListener');
        this._events = Object.create(null);
        this._eventsCount = 0;
        return this;
      }

      listeners = events[type];

      if (typeof listeners === 'function') {
        this.removeListener(type, listeners);
      } else if (listeners !== undefined) {
        // LIFO order
        for (i = listeners.length - 1; i >= 0; i--) {
          this.removeListener(type, listeners[i]);
        }
      }

      return this;
    };

function _listeners(target, type, unwrap) {
  var events = target._events;

  if (events === undefined)
    return [];

  var evlistener = events[type];
  if (evlistener === undefined)
    return [];

  if (typeof evlistener === 'function')
    return unwrap ? [evlistener.listener || evlistener] : [evlistener];

  return unwrap ?
    unwrapListeners(evlistener) : arrayClone(evlistener, evlistener.length);
}

EventEmitter.prototype.listeners = function listeners(type) {
  return _listeners(this, type, true);
};

EventEmitter.prototype.rawListeners = function rawListeners(type) {
  return _listeners(this, type, false);
};

EventEmitter.listenerCount = function(emitter, type) {
  if (typeof emitter.listenerCount === 'function') {
    return emitter.listenerCount(type);
  } else {
    return listenerCount.call(emitter, type);
  }
};

EventEmitter.prototype.listenerCount = listenerCount;
function listenerCount(type) {
  var events = this._events;

  if (events !== undefined) {
    var evlistener = events[type];

    if (typeof evlistener === 'function') {
      return 1;
    } else if (evlistener !== undefined) {
      return evlistener.length;
    }
  }

  return 0;
}

EventEmitter.prototype.eventNames = function eventNames() {
  return this._eventsCount > 0 ? ReflectOwnKeys(this._events) : [];
};

function arrayClone(arr, n) {
  var copy = new Array(n);
  for (var i = 0; i < n; ++i)
    copy[i] = arr[i];
  return copy;
}

function spliceOne(list, index) {
  for (; index + 1 < list.length; index++)
    list[index] = list[index + 1];
  list.pop();
}

function unwrapListeners(arr) {
  var ret = new Array(arr.length);
  for (var i = 0; i < ret.length; ++i) {
    ret[i] = arr[i].listener || arr[i];
  }
  return ret;
}

function once(emitter, name) {
  return new Promise(function (resolve, reject) {
    function errorListener(err) {
      emitter.removeListener(name, resolver);
      reject(err);
    }

    function resolver() {
      if (typeof emitter.removeListener === 'function') {
        emitter.removeListener('error', errorListener);
      }
      resolve([].slice.call(arguments));
    };

    eventTargetAgnosticAddListener(emitter, name, resolver, { once: true });
    if (name !== 'error') {
      addErrorHandlerIfEventEmitter(emitter, errorListener, { once: true });
    }
  });
}

function addErrorHandlerIfEventEmitter(emitter, handler, flags) {
  if (typeof emitter.on === 'function') {
    eventTargetAgnosticAddListener(emitter, 'error', handler, flags);
  }
}

function eventTargetAgnosticAddListener(emitter, name, listener, flags) {
  if (typeof emitter.on === 'function') {
    if (flags.once) {
      emitter.once(name, listener);
    } else {
      emitter.on(name, listener);
    }
  } else if (typeof emitter.addEventListener === 'function') {
    // EventTarget does not have `error` event semantics like Node
    // EventEmitters, we do not listen for `error` events here.
    emitter.addEventListener(name, function wrapListener(arg) {
      // IE does not have builtin `{ once: true }` support so we
      // have to do it manually.
      if (flags.once) {
        emitter.removeEventListener(name, wrapListener);
      }
      listener(arg);
    });
  } else {
    throw new TypeError('The "emitter" argument must be of type EventEmitter. Received type ' + typeof emitter);
  }
}


/***/ }),

/***/ "./node_modules/lodash.throttle/index.js":
/*!***********************************************!*\
  !*** ./node_modules/lodash.throttle/index.js ***!
  \***********************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

/**
 * lodash (Custom Build) <https://lodash.com/>
 * Build: `lodash modularize exports="npm" -o ./`
 * Copyright jQuery Foundation and other contributors <https://jquery.org/>
 * Released under MIT license <https://lodash.com/license>
 * Based on Underscore.js 1.8.3 <http://underscorejs.org/LICENSE>
 * Copyright Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors
 */

/** Used as the `TypeError` message for "Functions" methods. */
var FUNC_ERROR_TEXT = 'Expected a function';

/** Used as references for various `Number` constants. */
var NAN = 0 / 0;

/** `Object#toString` result references. */
var symbolTag = '[object Symbol]';

/** Used to match leading and trailing whitespace. */
var reTrim = /^\s+|\s+$/g;

/** Used to detect bad signed hexadecimal string values. */
var reIsBadHex = /^[-+]0x[0-9a-f]+$/i;

/** Used to detect binary string values. */
var reIsBinary = /^0b[01]+$/i;

/** Used to detect octal string values. */
var reIsOctal = /^0o[0-7]+$/i;

/** Built-in method references without a dependency on `root`. */
var freeParseInt = parseInt;

/** Detect free variable `global` from Node.js. */
var freeGlobal = typeof __webpack_require__.g == 'object' && __webpack_require__.g && __webpack_require__.g.Object === Object && __webpack_require__.g;

/** Detect free variable `self`. */
var freeSelf = typeof self == 'object' && self && self.Object === Object && self;

/** Used as a reference to the global object. */
var root = freeGlobal || freeSelf || Function('return this')();

/** Used for built-in method references. */
var objectProto = Object.prototype;

/**
 * Used to resolve the
 * [`toStringTag`](http://ecma-international.org/ecma-262/7.0/#sec-object.prototype.tostring)
 * of values.
 */
var objectToString = objectProto.toString;

/* Built-in method references for those with the same name as other `lodash` methods. */
var nativeMax = Math.max,
    nativeMin = Math.min;

/**
 * Gets the timestamp of the number of milliseconds that have elapsed since
 * the Unix epoch (1 January 1970 00:00:00 UTC).
 *
 * @static
 * @memberOf _
 * @since 2.4.0
 * @category Date
 * @returns {number} Returns the timestamp.
 * @example
 *
 * _.defer(function(stamp) {
 *   console.log(_.now() - stamp);
 * }, _.now());
 * // => Logs the number of milliseconds it took for the deferred invocation.
 */
var now = function() {
  return root.Date.now();
};

/**
 * Creates a debounced function that delays invoking `func` until after `wait`
 * milliseconds have elapsed since the last time the debounced function was
 * invoked. The debounced function comes with a `cancel` method to cancel
 * delayed `func` invocations and a `flush` method to immediately invoke them.
 * Provide `options` to indicate whether `func` should be invoked on the
 * leading and/or trailing edge of the `wait` timeout. The `func` is invoked
 * with the last arguments provided to the debounced function. Subsequent
 * calls to the debounced function return the result of the last `func`
 * invocation.
 *
 * **Note:** If `leading` and `trailing` options are `true`, `func` is
 * invoked on the trailing edge of the timeout only if the debounced function
 * is invoked more than once during the `wait` timeout.
 *
 * If `wait` is `0` and `leading` is `false`, `func` invocation is deferred
 * until to the next tick, similar to `setTimeout` with a timeout of `0`.
 *
 * See [David Corbacho's article](https://css-tricks.com/debouncing-throttling-explained-examples/)
 * for details over the differences between `_.debounce` and `_.throttle`.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Function
 * @param {Function} func The function to debounce.
 * @param {number} [wait=0] The number of milliseconds to delay.
 * @param {Object} [options={}] The options object.
 * @param {boolean} [options.leading=false]
 *  Specify invoking on the leading edge of the timeout.
 * @param {number} [options.maxWait]
 *  The maximum time `func` is allowed to be delayed before it's invoked.
 * @param {boolean} [options.trailing=true]
 *  Specify invoking on the trailing edge of the timeout.
 * @returns {Function} Returns the new debounced function.
 * @example
 *
 * // Avoid costly calculations while the window size is in flux.
 * jQuery(window).on('resize', _.debounce(calculateLayout, 150));
 *
 * // Invoke `sendMail` when clicked, debouncing subsequent calls.
 * jQuery(element).on('click', _.debounce(sendMail, 300, {
 *   'leading': true,
 *   'trailing': false
 * }));
 *
 * // Ensure `batchLog` is invoked once after 1 second of debounced calls.
 * var debounced = _.debounce(batchLog, 250, { 'maxWait': 1000 });
 * var source = new EventSource('/stream');
 * jQuery(source).on('message', debounced);
 *
 * // Cancel the trailing debounced invocation.
 * jQuery(window).on('popstate', debounced.cancel);
 */
function debounce(func, wait, options) {
  var lastArgs,
      lastThis,
      maxWait,
      result,
      timerId,
      lastCallTime,
      lastInvokeTime = 0,
      leading = false,
      maxing = false,
      trailing = true;

  if (typeof func != 'function') {
    throw new TypeError(FUNC_ERROR_TEXT);
  }
  wait = toNumber(wait) || 0;
  if (isObject(options)) {
    leading = !!options.leading;
    maxing = 'maxWait' in options;
    maxWait = maxing ? nativeMax(toNumber(options.maxWait) || 0, wait) : maxWait;
    trailing = 'trailing' in options ? !!options.trailing : trailing;
  }

  function invokeFunc(time) {
    var args = lastArgs,
        thisArg = lastThis;

    lastArgs = lastThis = undefined;
    lastInvokeTime = time;
    result = func.apply(thisArg, args);
    return result;
  }

  function leadingEdge(time) {
    // Reset any `maxWait` timer.
    lastInvokeTime = time;
    // Start the timer for the trailing edge.
    timerId = setTimeout(timerExpired, wait);
    // Invoke the leading edge.
    return leading ? invokeFunc(time) : result;
  }

  function remainingWait(time) {
    var timeSinceLastCall = time - lastCallTime,
        timeSinceLastInvoke = time - lastInvokeTime,
        result = wait - timeSinceLastCall;

    return maxing ? nativeMin(result, maxWait - timeSinceLastInvoke) : result;
  }

  function shouldInvoke(time) {
    var timeSinceLastCall = time - lastCallTime,
        timeSinceLastInvoke = time - lastInvokeTime;

    // Either this is the first call, activity has stopped and we're at the
    // trailing edge, the system time has gone backwards and we're treating
    // it as the trailing edge, or we've hit the `maxWait` limit.
    return (lastCallTime === undefined || (timeSinceLastCall >= wait) ||
      (timeSinceLastCall < 0) || (maxing && timeSinceLastInvoke >= maxWait));
  }

  function timerExpired() {
    var time = now();
    if (shouldInvoke(time)) {
      return trailingEdge(time);
    }
    // Restart the timer.
    timerId = setTimeout(timerExpired, remainingWait(time));
  }

  function trailingEdge(time) {
    timerId = undefined;

    // Only invoke if we have `lastArgs` which means `func` has been
    // debounced at least once.
    if (trailing && lastArgs) {
      return invokeFunc(time);
    }
    lastArgs = lastThis = undefined;
    return result;
  }

  function cancel() {
    if (timerId !== undefined) {
      clearTimeout(timerId);
    }
    lastInvokeTime = 0;
    lastArgs = lastCallTime = lastThis = timerId = undefined;
  }

  function flush() {
    return timerId === undefined ? result : trailingEdge(now());
  }

  function debounced() {
    var time = now(),
        isInvoking = shouldInvoke(time);

    lastArgs = arguments;
    lastThis = this;
    lastCallTime = time;

    if (isInvoking) {
      if (timerId === undefined) {
        return leadingEdge(lastCallTime);
      }
      if (maxing) {
        // Handle invocations in a tight loop.
        timerId = setTimeout(timerExpired, wait);
        return invokeFunc(lastCallTime);
      }
    }
    if (timerId === undefined) {
      timerId = setTimeout(timerExpired, wait);
    }
    return result;
  }
  debounced.cancel = cancel;
  debounced.flush = flush;
  return debounced;
}

/**
 * Creates a throttled function that only invokes `func` at most once per
 * every `wait` milliseconds. The throttled function comes with a `cancel`
 * method to cancel delayed `func` invocations and a `flush` method to
 * immediately invoke them. Provide `options` to indicate whether `func`
 * should be invoked on the leading and/or trailing edge of the `wait`
 * timeout. The `func` is invoked with the last arguments provided to the
 * throttled function. Subsequent calls to the throttled function return the
 * result of the last `func` invocation.
 *
 * **Note:** If `leading` and `trailing` options are `true`, `func` is
 * invoked on the trailing edge of the timeout only if the throttled function
 * is invoked more than once during the `wait` timeout.
 *
 * If `wait` is `0` and `leading` is `false`, `func` invocation is deferred
 * until to the next tick, similar to `setTimeout` with a timeout of `0`.
 *
 * See [David Corbacho's article](https://css-tricks.com/debouncing-throttling-explained-examples/)
 * for details over the differences between `_.throttle` and `_.debounce`.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Function
 * @param {Function} func The function to throttle.
 * @param {number} [wait=0] The number of milliseconds to throttle invocations to.
 * @param {Object} [options={}] The options object.
 * @param {boolean} [options.leading=true]
 *  Specify invoking on the leading edge of the timeout.
 * @param {boolean} [options.trailing=true]
 *  Specify invoking on the trailing edge of the timeout.
 * @returns {Function} Returns the new throttled function.
 * @example
 *
 * // Avoid excessively updating the position while scrolling.
 * jQuery(window).on('scroll', _.throttle(updatePosition, 100));
 *
 * // Invoke `renewToken` when the click event is fired, but not more than once every 5 minutes.
 * var throttled = _.throttle(renewToken, 300000, { 'trailing': false });
 * jQuery(element).on('click', throttled);
 *
 * // Cancel the trailing throttled invocation.
 * jQuery(window).on('popstate', throttled.cancel);
 */
function throttle(func, wait, options) {
  var leading = true,
      trailing = true;

  if (typeof func != 'function') {
    throw new TypeError(FUNC_ERROR_TEXT);
  }
  if (isObject(options)) {
    leading = 'leading' in options ? !!options.leading : leading;
    trailing = 'trailing' in options ? !!options.trailing : trailing;
  }
  return debounce(func, wait, {
    'leading': leading,
    'maxWait': wait,
    'trailing': trailing
  });
}

/**
 * Checks if `value` is the
 * [language type](http://www.ecma-international.org/ecma-262/7.0/#sec-ecmascript-language-types)
 * of `Object`. (e.g. arrays, functions, objects, regexes, `new Number(0)`, and `new String('')`)
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an object, else `false`.
 * @example
 *
 * _.isObject({});
 * // => true
 *
 * _.isObject([1, 2, 3]);
 * // => true
 *
 * _.isObject(_.noop);
 * // => true
 *
 * _.isObject(null);
 * // => false
 */
function isObject(value) {
  var type = typeof value;
  return !!value && (type == 'object' || type == 'function');
}

/**
 * Checks if `value` is object-like. A value is object-like if it's not `null`
 * and has a `typeof` result of "object".
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is object-like, else `false`.
 * @example
 *
 * _.isObjectLike({});
 * // => true
 *
 * _.isObjectLike([1, 2, 3]);
 * // => true
 *
 * _.isObjectLike(_.noop);
 * // => false
 *
 * _.isObjectLike(null);
 * // => false
 */
function isObjectLike(value) {
  return !!value && typeof value == 'object';
}

/**
 * Checks if `value` is classified as a `Symbol` primitive or object.
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a symbol, else `false`.
 * @example
 *
 * _.isSymbol(Symbol.iterator);
 * // => true
 *
 * _.isSymbol('abc');
 * // => false
 */
function isSymbol(value) {
  return typeof value == 'symbol' ||
    (isObjectLike(value) && objectToString.call(value) == symbolTag);
}

/**
 * Converts `value` to a number.
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to process.
 * @returns {number} Returns the number.
 * @example
 *
 * _.toNumber(3.2);
 * // => 3.2
 *
 * _.toNumber(Number.MIN_VALUE);
 * // => 5e-324
 *
 * _.toNumber(Infinity);
 * // => Infinity
 *
 * _.toNumber('3.2');
 * // => 3.2
 */
function toNumber(value) {
  if (typeof value == 'number') {
    return value;
  }
  if (isSymbol(value)) {
    return NAN;
  }
  if (isObject(value)) {
    var other = typeof value.valueOf == 'function' ? value.valueOf() : value;
    value = isObject(other) ? (other + '') : other;
  }
  if (typeof value != 'string') {
    return value === 0 ? value : +value;
  }
  value = value.replace(reTrim, '');
  var isBinary = reIsBinary.test(value);
  return (isBinary || reIsOctal.test(value))
    ? freeParseInt(value.slice(2), isBinary ? 2 : 8)
    : (reIsBadHex.test(value) ? NAN : +value);
}

module.exports = throttle;


/***/ }),

/***/ "./node_modules/mime-match/index.js":
/*!******************************************!*\
  !*** ./node_modules/mime-match/index.js ***!
  \******************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

var wildcard = __webpack_require__(/*! wildcard */ "./node_modules/wildcard/index.js");
var reMimePartSplit = /[\/\+\.]/;

/**
  # mime-match

  A simple function to checker whether a target mime type matches a mime-type
  pattern (e.g. image/jpeg matches image/jpeg OR image/*).

  ## Example Usage

  <<< example.js

**/
module.exports = function(target, pattern) {
  function test(pattern) {
    var result = wildcard(pattern, target, reMimePartSplit);

    // ensure that we have a valid mime type (should have two parts)
    return result && result.length >= 2;
  }

  return pattern ? test(pattern.split(';')[0]) : test;
};


/***/ }),

/***/ "./node_modules/namespace-emitter/index.js":
/*!*************************************************!*\
  !*** ./node_modules/namespace-emitter/index.js ***!
  \*************************************************/
/***/ ((module) => {

/**
* Create an event emitter with namespaces
* @name createNamespaceEmitter
* @example
* var emitter = require('./index')()
*
* emitter.on('*', function () {
*   console.log('all events emitted', this.event)
* })
*
* emitter.on('example', function () {
*   console.log('example event emitted')
* })
*/
module.exports = function createNamespaceEmitter () {
  var emitter = {}
  var _fns = emitter._fns = {}

  /**
  * Emit an event. Optionally namespace the event. Handlers are fired in the order in which they were added with exact matches taking precedence. Separate the namespace and event with a `:`
  * @name emit
  * @param {String} event – the name of the event, with optional namespace
  * @param {...*} data – up to 6 arguments that are passed to the event listener
  * @example
  * emitter.emit('example')
  * emitter.emit('demo:test')
  * emitter.emit('data', { example: true}, 'a string', 1)
  */
  emitter.emit = function emit (event, arg1, arg2, arg3, arg4, arg5, arg6) {
    var toEmit = getListeners(event)

    if (toEmit.length) {
      emitAll(event, toEmit, [arg1, arg2, arg3, arg4, arg5, arg6])
    }
  }

  /**
  * Create en event listener.
  * @name on
  * @param {String} event
  * @param {Function} fn
  * @example
  * emitter.on('example', function () {})
  * emitter.on('demo', function () {})
  */
  emitter.on = function on (event, fn) {
    if (!_fns[event]) {
      _fns[event] = []
    }

    _fns[event].push(fn)
  }

  /**
  * Create en event listener that fires once.
  * @name once
  * @param {String} event
  * @param {Function} fn
  * @example
  * emitter.once('example', function () {})
  * emitter.once('demo', function () {})
  */
  emitter.once = function once (event, fn) {
    function one () {
      fn.apply(this, arguments)
      emitter.off(event, one)
    }
    this.on(event, one)
  }

  /**
  * Stop listening to an event. Stop all listeners on an event by only passing the event name. Stop a single listener by passing that event handler as a callback.
  * You must be explicit about what will be unsubscribed: `emitter.off('demo')` will unsubscribe an `emitter.on('demo')` listener,
  * `emitter.off('demo:example')` will unsubscribe an `emitter.on('demo:example')` listener
  * @name off
  * @param {String} event
  * @param {Function} [fn] – the specific handler
  * @example
  * emitter.off('example')
  * emitter.off('demo', function () {})
  */
  emitter.off = function off (event, fn) {
    var keep = []

    if (event && fn) {
      var fns = this._fns[event]
      var i = 0
      var l = fns ? fns.length : 0

      for (i; i < l; i++) {
        if (fns[i] !== fn) {
          keep.push(fns[i])
        }
      }
    }

    keep.length ? this._fns[event] = keep : delete this._fns[event]
  }

  function getListeners (e) {
    var out = _fns[e] ? _fns[e] : []
    var idx = e.indexOf(':')
    var args = (idx === -1) ? [e] : [e.substring(0, idx), e.substring(idx + 1)]

    var keys = Object.keys(_fns)
    var i = 0
    var l = keys.length

    for (i; i < l; i++) {
      var key = keys[i]
      if (key === '*') {
        out = out.concat(_fns[key])
      }

      if (args.length === 2 && args[0] === key) {
        out = out.concat(_fns[key])
        break
      }
    }

    return out
  }

  function emitAll (e, fns, args) {
    var i = 0
    var l = fns.length

    for (i; i < l; i++) {
      if (!fns[i]) break
      fns[i].event = e
      fns[i].apply(fns[i], args)
    }
  }

  return emitter
}


/***/ }),

/***/ "./node_modules/preact/dist/preact.module.js":
/*!***************************************************!*\
  !*** ./node_modules/preact/dist/preact.module.js ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Component: () => (/* binding */ b),
/* harmony export */   Fragment: () => (/* binding */ k),
/* harmony export */   cloneElement: () => (/* binding */ F),
/* harmony export */   createContext: () => (/* binding */ G),
/* harmony export */   createElement: () => (/* binding */ y),
/* harmony export */   createRef: () => (/* binding */ _),
/* harmony export */   h: () => (/* binding */ y),
/* harmony export */   hydrate: () => (/* binding */ E),
/* harmony export */   isValidElement: () => (/* binding */ i),
/* harmony export */   options: () => (/* binding */ l),
/* harmony export */   render: () => (/* binding */ D),
/* harmony export */   toChildArray: () => (/* binding */ S)
/* harmony export */ });
var n,l,u,i,t,o,r,f,e,c={},s=[],a=/acit|ex(?:s|g|n|p|$)|rph|grid|ows|mnc|ntw|ine[ch]|zoo|^ord|itera/i,v=Array.isArray;function h(n,l){for(var u in l)n[u]=l[u];return n}function p(n){var l=n.parentNode;l&&l.removeChild(n)}function y(l,u,i){var t,o,r,f={};for(r in u)"key"==r?t=u[r]:"ref"==r?o=u[r]:f[r]=u[r];if(arguments.length>2&&(f.children=arguments.length>3?n.call(arguments,2):i),"function"==typeof l&&null!=l.defaultProps)for(r in l.defaultProps)void 0===f[r]&&(f[r]=l.defaultProps[r]);return d(l,f,t,o,null)}function d(n,i,t,o,r){var f={type:n,props:i,key:t,ref:o,__k:null,__:null,__b:0,__e:null,__d:void 0,__c:null,__h:null,constructor:void 0,__v:null==r?++u:r};return null==r&&null!=l.vnode&&l.vnode(f),f}function _(){return{current:null}}function k(n){return n.children}function b(n,l){this.props=n,this.context=l}function g(n,l){if(null==l)return n.__?g(n.__,n.__.__k.indexOf(n)+1):null;for(var u;l<n.__k.length;l++)if(null!=(u=n.__k[l])&&null!=u.__e)return u.__e;return"function"==typeof n.type?g(n):null}function m(n){var l,u;if(null!=(n=n.__)&&null!=n.__c){for(n.__e=n.__c.base=null,l=0;l<n.__k.length;l++)if(null!=(u=n.__k[l])&&null!=u.__e){n.__e=n.__c.base=u.__e;break}return m(n)}}function w(n){(!n.__d&&(n.__d=!0)&&t.push(n)&&!x.__r++||o!==l.debounceRendering)&&((o=l.debounceRendering)||r)(x)}function x(){var n,l,u,i,o,r,e,c;for(t.sort(f);n=t.shift();)n.__d&&(l=t.length,i=void 0,o=void 0,e=(r=(u=n).__v).__e,(c=u.__P)&&(i=[],(o=h({},r)).__v=r.__v+1,L(c,r,o,u.__n,void 0!==c.ownerSVGElement,null!=r.__h?[e]:null,i,null==e?g(r):e,r.__h),M(i,r),r.__e!=e&&m(r)),t.length>l&&t.sort(f));x.__r=0}function P(n,l,u,i,t,o,r,f,e,a){var h,p,y,_,b,m,w,x=i&&i.__k||s,P=x.length;for(u.__k=[],h=0;h<l.length;h++)if(null!=(_=u.__k[h]=null==(_=l[h])||"boolean"==typeof _||"function"==typeof _?null:"string"==typeof _||"number"==typeof _||"bigint"==typeof _?d(null,_,null,null,_):v(_)?d(k,{children:_},null,null,null):_.__b>0?d(_.type,_.props,_.key,_.ref?_.ref:null,_.__v):_)){if(_.__=u,_.__b=u.__b+1,null===(y=x[h])||y&&_.key==y.key&&_.type===y.type)x[h]=void 0;else for(p=0;p<P;p++){if((y=x[p])&&_.key==y.key&&_.type===y.type){x[p]=void 0;break}y=null}L(n,_,y=y||c,t,o,r,f,e,a),b=_.__e,(p=_.ref)&&y.ref!=p&&(w||(w=[]),y.ref&&w.push(y.ref,null,_),w.push(p,_.__c||b,_)),null!=b?(null==m&&(m=b),"function"==typeof _.type&&_.__k===y.__k?_.__d=e=C(_,e,n):e=$(n,_,y,x,b,e),"function"==typeof u.type&&(u.__d=e)):e&&y.__e==e&&e.parentNode!=n&&(e=g(y))}for(u.__e=m,h=P;h--;)null!=x[h]&&("function"==typeof u.type&&null!=x[h].__e&&x[h].__e==u.__d&&(u.__d=A(i).nextSibling),q(x[h],x[h]));if(w)for(h=0;h<w.length;h++)O(w[h],w[++h],w[++h])}function C(n,l,u){for(var i,t=n.__k,o=0;t&&o<t.length;o++)(i=t[o])&&(i.__=n,l="function"==typeof i.type?C(i,l,u):$(u,i,i,t,i.__e,l));return l}function S(n,l){return l=l||[],null==n||"boolean"==typeof n||(v(n)?n.some(function(n){S(n,l)}):l.push(n)),l}function $(n,l,u,i,t,o){var r,f,e;if(void 0!==l.__d)r=l.__d,l.__d=void 0;else if(null==u||t!=o||null==t.parentNode)n:if(null==o||o.parentNode!==n)n.appendChild(t),r=null;else{for(f=o,e=0;(f=f.nextSibling)&&e<i.length;e+=1)if(f==t)break n;n.insertBefore(t,o),r=o}return void 0!==r?r:t.nextSibling}function A(n){var l,u,i;if(null==n.type||"string"==typeof n.type)return n.__e;if(n.__k)for(l=n.__k.length-1;l>=0;l--)if((u=n.__k[l])&&(i=A(u)))return i;return null}function H(n,l,u,i,t){var o;for(o in u)"children"===o||"key"===o||o in l||T(n,o,null,u[o],i);for(o in l)t&&"function"!=typeof l[o]||"children"===o||"key"===o||"value"===o||"checked"===o||u[o]===l[o]||T(n,o,l[o],u[o],i)}function I(n,l,u){"-"===l[0]?n.setProperty(l,null==u?"":u):n[l]=null==u?"":"number"!=typeof u||a.test(l)?u:u+"px"}function T(n,l,u,i,t){var o;n:if("style"===l)if("string"==typeof u)n.style.cssText=u;else{if("string"==typeof i&&(n.style.cssText=i=""),i)for(l in i)u&&l in u||I(n.style,l,"");if(u)for(l in u)i&&u[l]===i[l]||I(n.style,l,u[l])}else if("o"===l[0]&&"n"===l[1])o=l!==(l=l.replace(/Capture$/,"")),l=l.toLowerCase()in n?l.toLowerCase().slice(2):l.slice(2),n.l||(n.l={}),n.l[l+o]=u,u?i||n.addEventListener(l,o?z:j,o):n.removeEventListener(l,o?z:j,o);else if("dangerouslySetInnerHTML"!==l){if(t)l=l.replace(/xlink(H|:h)/,"h").replace(/sName$/,"s");else if("width"!==l&&"height"!==l&&"href"!==l&&"list"!==l&&"form"!==l&&"tabIndex"!==l&&"download"!==l&&"rowSpan"!==l&&"colSpan"!==l&&l in n)try{n[l]=null==u?"":u;break n}catch(n){}"function"==typeof u||(null==u||!1===u&&"-"!==l[4]?n.removeAttribute(l):n.setAttribute(l,u))}}function j(n){return this.l[n.type+!1](l.event?l.event(n):n)}function z(n){return this.l[n.type+!0](l.event?l.event(n):n)}function L(n,u,i,t,o,r,f,e,c){var s,a,p,y,d,_,g,m,w,x,C,S,$,A,H,I=u.type;if(void 0!==u.constructor)return null;null!=i.__h&&(c=i.__h,e=u.__e=i.__e,u.__h=null,r=[e]),(s=l.__b)&&s(u);try{n:if("function"==typeof I){if(m=u.props,w=(s=I.contextType)&&t[s.__c],x=s?w?w.props.value:s.__:t,i.__c?g=(a=u.__c=i.__c).__=a.__E:("prototype"in I&&I.prototype.render?u.__c=a=new I(m,x):(u.__c=a=new b(m,x),a.constructor=I,a.render=B),w&&w.sub(a),a.props=m,a.state||(a.state={}),a.context=x,a.__n=t,p=a.__d=!0,a.__h=[],a._sb=[]),null==a.__s&&(a.__s=a.state),null!=I.getDerivedStateFromProps&&(a.__s==a.state&&(a.__s=h({},a.__s)),h(a.__s,I.getDerivedStateFromProps(m,a.__s))),y=a.props,d=a.state,a.__v=u,p)null==I.getDerivedStateFromProps&&null!=a.componentWillMount&&a.componentWillMount(),null!=a.componentDidMount&&a.__h.push(a.componentDidMount);else{if(null==I.getDerivedStateFromProps&&m!==y&&null!=a.componentWillReceiveProps&&a.componentWillReceiveProps(m,x),!a.__e&&null!=a.shouldComponentUpdate&&!1===a.shouldComponentUpdate(m,a.__s,x)||u.__v===i.__v){for(u.__v!==i.__v&&(a.props=m,a.state=a.__s,a.__d=!1),a.__e=!1,u.__e=i.__e,u.__k=i.__k,u.__k.forEach(function(n){n&&(n.__=u)}),C=0;C<a._sb.length;C++)a.__h.push(a._sb[C]);a._sb=[],a.__h.length&&f.push(a);break n}null!=a.componentWillUpdate&&a.componentWillUpdate(m,a.__s,x),null!=a.componentDidUpdate&&a.__h.push(function(){a.componentDidUpdate(y,d,_)})}if(a.context=x,a.props=m,a.__P=n,S=l.__r,$=0,"prototype"in I&&I.prototype.render){for(a.state=a.__s,a.__d=!1,S&&S(u),s=a.render(a.props,a.state,a.context),A=0;A<a._sb.length;A++)a.__h.push(a._sb[A]);a._sb=[]}else do{a.__d=!1,S&&S(u),s=a.render(a.props,a.state,a.context),a.state=a.__s}while(a.__d&&++$<25);a.state=a.__s,null!=a.getChildContext&&(t=h(h({},t),a.getChildContext())),p||null==a.getSnapshotBeforeUpdate||(_=a.getSnapshotBeforeUpdate(y,d)),P(n,v(H=null!=s&&s.type===k&&null==s.key?s.props.children:s)?H:[H],u,i,t,o,r,f,e,c),a.base=u.__e,u.__h=null,a.__h.length&&f.push(a),g&&(a.__E=a.__=null),a.__e=!1}else null==r&&u.__v===i.__v?(u.__k=i.__k,u.__e=i.__e):u.__e=N(i.__e,u,i,t,o,r,f,c);(s=l.diffed)&&s(u)}catch(n){u.__v=null,(c||null!=r)&&(u.__e=e,u.__h=!!c,r[r.indexOf(e)]=null),l.__e(n,u,i)}}function M(n,u){l.__c&&l.__c(u,n),n.some(function(u){try{n=u.__h,u.__h=[],n.some(function(n){n.call(u)})}catch(n){l.__e(n,u.__v)}})}function N(l,u,i,t,o,r,f,e){var s,a,h,y=i.props,d=u.props,_=u.type,k=0;if("svg"===_&&(o=!0),null!=r)for(;k<r.length;k++)if((s=r[k])&&"setAttribute"in s==!!_&&(_?s.localName===_:3===s.nodeType)){l=s,r[k]=null;break}if(null==l){if(null===_)return document.createTextNode(d);l=o?document.createElementNS("http://www.w3.org/2000/svg",_):document.createElement(_,d.is&&d),r=null,e=!1}if(null===_)y===d||e&&l.data===d||(l.data=d);else{if(r=r&&n.call(l.childNodes),a=(y=i.props||c).dangerouslySetInnerHTML,h=d.dangerouslySetInnerHTML,!e){if(null!=r)for(y={},k=0;k<l.attributes.length;k++)y[l.attributes[k].name]=l.attributes[k].value;(h||a)&&(h&&(a&&h.__html==a.__html||h.__html===l.innerHTML)||(l.innerHTML=h&&h.__html||""))}if(H(l,d,y,o,e),h)u.__k=[];else if(P(l,v(k=u.props.children)?k:[k],u,i,t,o&&"foreignObject"!==_,r,f,r?r[0]:i.__k&&g(i,0),e),null!=r)for(k=r.length;k--;)null!=r[k]&&p(r[k]);e||("value"in d&&void 0!==(k=d.value)&&(k!==l.value||"progress"===_&&!k||"option"===_&&k!==y.value)&&T(l,"value",k,y.value,!1),"checked"in d&&void 0!==(k=d.checked)&&k!==l.checked&&T(l,"checked",k,y.checked,!1))}return l}function O(n,u,i){try{"function"==typeof n?n(u):n.current=u}catch(n){l.__e(n,i)}}function q(n,u,i){var t,o;if(l.unmount&&l.unmount(n),(t=n.ref)&&(t.current&&t.current!==n.__e||O(t,null,u)),null!=(t=n.__c)){if(t.componentWillUnmount)try{t.componentWillUnmount()}catch(n){l.__e(n,u)}t.base=t.__P=null,n.__c=void 0}if(t=n.__k)for(o=0;o<t.length;o++)t[o]&&q(t[o],u,i||"function"!=typeof n.type);i||null==n.__e||p(n.__e),n.__=n.__e=n.__d=void 0}function B(n,l,u){return this.constructor(n,u)}function D(u,i,t){var o,r,f;l.__&&l.__(u,i),r=(o="function"==typeof t)?null:t&&t.__k||i.__k,f=[],L(i,u=(!o&&t||i).__k=y(k,null,[u]),r||c,c,void 0!==i.ownerSVGElement,!o&&t?[t]:r?null:i.firstChild?n.call(i.childNodes):null,f,!o&&t?t:r?r.__e:i.firstChild,o),M(f,u)}function E(n,l){D(n,l,E)}function F(l,u,i){var t,o,r,f,e=h({},l.props);for(r in l.type&&l.type.defaultProps&&(f=l.type.defaultProps),u)"key"==r?t=u[r]:"ref"==r?o=u[r]:e[r]=void 0===u[r]&&void 0!==f?f[r]:u[r];return arguments.length>2&&(e.children=arguments.length>3?n.call(arguments,2):i),d(l.type,e,t||l.key,o||l.ref,null)}function G(n,l){var u={__c:l="__cC"+e++,__:n,Consumer:function(n,l){return n.children(l)},Provider:function(n){var u,i;return this.getChildContext||(u=[],(i={})[l]=this,this.getChildContext=function(){return i},this.shouldComponentUpdate=function(n){this.props.value!==n.value&&u.some(function(n){n.__e=!0,w(n)})},this.sub=function(n){u.push(n);var l=n.componentWillUnmount;n.componentWillUnmount=function(){u.splice(u.indexOf(n),1),l&&l.call(n)}}),n.children}};return u.Provider.__=u.Consumer.contextType=u}n=s.slice,l={__e:function(n,l,u,i){for(var t,o,r;l=l.__;)if((t=l.__c)&&!t.__)try{if((o=t.constructor)&&null!=o.getDerivedStateFromError&&(t.setState(o.getDerivedStateFromError(n)),r=t.__d),null!=t.componentDidCatch&&(t.componentDidCatch(n,i||{}),r=t.__d),r)return t.__E=t}catch(l){n=l}throw n}},u=0,i=function(n){return null!=n&&void 0===n.constructor},b.prototype.setState=function(n,l){var u;u=null!=this.__s&&this.__s!==this.state?this.__s:this.__s=h({},this.state),"function"==typeof n&&(n=n(h({},u),this.props)),n&&h(u,n),null!=n&&this.__v&&(l&&this._sb.push(l),w(this))},b.prototype.forceUpdate=function(n){this.__v&&(this.__e=!0,n&&this.__h.push(n),w(this))},b.prototype.render=k,t=[],r="function"==typeof Promise?Promise.prototype.then.bind(Promise.resolve()):setTimeout,f=function(n,l){return n.__v.__b-l.__v.__b},x.__r=0,e=0;
//# sourceMappingURL=preact.module.js.map


/***/ }),

/***/ "./node_modules/querystringify/index.js":
/*!**********************************************!*\
  !*** ./node_modules/querystringify/index.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, exports) => {

"use strict";


var has = Object.prototype.hasOwnProperty
  , undef;

/**
 * Decode a URI encoded string.
 *
 * @param {String} input The URI encoded string.
 * @returns {String|Null} The decoded string.
 * @api private
 */
function decode(input) {
  try {
    return decodeURIComponent(input.replace(/\+/g, ' '));
  } catch (e) {
    return null;
  }
}

/**
 * Attempts to encode a given input.
 *
 * @param {String} input The string that needs to be encoded.
 * @returns {String|Null} The encoded string.
 * @api private
 */
function encode(input) {
  try {
    return encodeURIComponent(input);
  } catch (e) {
    return null;
  }
}

/**
 * Simple query string parser.
 *
 * @param {String} query The query string that needs to be parsed.
 * @returns {Object}
 * @api public
 */
function querystring(query) {
  var parser = /([^=?#&]+)=?([^&]*)/g
    , result = {}
    , part;

  while (part = parser.exec(query)) {
    var key = decode(part[1])
      , value = decode(part[2]);

    //
    // Prevent overriding of existing properties. This ensures that build-in
    // methods like `toString` or __proto__ are not overriden by malicious
    // querystrings.
    //
    // In the case if failed decoding, we want to omit the key/value pairs
    // from the result.
    //
    if (key === null || value === null || key in result) continue;
    result[key] = value;
  }

  return result;
}

/**
 * Transform a query string to an object.
 *
 * @param {Object} obj Object that should be transformed.
 * @param {String} prefix Optional prefix.
 * @returns {String}
 * @api public
 */
function querystringify(obj, prefix) {
  prefix = prefix || '';

  var pairs = []
    , value
    , key;

  //
  // Optionally prefix with a '?' if needed
  //
  if ('string' !== typeof prefix) prefix = '?';

  for (key in obj) {
    if (has.call(obj, key)) {
      value = obj[key];

      //
      // Edge cases where we actually want to encode the value to an empty
      // string instead of the stringified value.
      //
      if (!value && (value === null || value === undef || isNaN(value))) {
        value = '';
      }

      key = encode(key);
      value = encode(value);

      //
      // If we failed to encode the strings, we should bail out as we don't
      // want to add invalid strings to the query.
      //
      if (key === null || value === null) continue;
      pairs.push(key +'='+ value);
    }
  }

  return pairs.length ? prefix + pairs.join('&') : '';
}

//
// Expose the module.
//
exports.stringify = querystringify;
exports.parse = querystring;


/***/ }),

/***/ "./node_modules/requires-port/index.js":
/*!*********************************************!*\
  !*** ./node_modules/requires-port/index.js ***!
  \*********************************************/
/***/ ((module) => {

"use strict";


/**
 * Check if we're required to add a port number.
 *
 * @see https://url.spec.whatwg.org/#default-port
 * @param {Number|String} port Port number we need to check
 * @param {String} protocol Protocol we need to check against.
 * @returns {Boolean} Is it a default port for the given protocol
 * @api private
 */
module.exports = function required(port, protocol) {
  protocol = protocol.split(':')[0];
  port = +port;

  if (!port) return false;

  switch (protocol) {
    case 'http':
    case 'ws':
    return port !== 80;

    case 'https':
    case 'wss':
    return port !== 443;

    case 'ftp':
    return port !== 21;

    case 'gopher':
    return port !== 70;

    case 'file':
    return false;
  }

  return port !== 0;
};


/***/ }),

/***/ "./node_modules/tus-js-client/lib.esm/browser/fileReader.js":
/*!******************************************************************!*\
  !*** ./node_modules/tus-js-client/lib.esm/browser/fileReader.js ***!
  \******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ FileReader)
/* harmony export */ });
/* harmony import */ var _isReactNative_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./isReactNative.js */ "./node_modules/tus-js-client/lib.esm/browser/isReactNative.js");
/* harmony import */ var _uriToBlob_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./uriToBlob.js */ "./node_modules/tus-js-client/lib.esm/browser/uriToBlob.js");
/* harmony import */ var _sources_FileSource_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./sources/FileSource.js */ "./node_modules/tus-js-client/lib.esm/browser/sources/FileSource.js");
/* harmony import */ var _sources_StreamSource_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./sources/StreamSource.js */ "./node_modules/tus-js-client/lib.esm/browser/sources/StreamSource.js");
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }






var FileReader = /*#__PURE__*/function () {
  function FileReader() {
    _classCallCheck(this, FileReader);
  }

  _createClass(FileReader, [{
    key: "openFile",
    value: function openFile(input, chunkSize) {
      // In React Native, when user selects a file, instead of a File or Blob,
      // you usually get a file object {} with a uri property that contains
      // a local path to the file. We use XMLHttpRequest to fetch
      // the file blob, before uploading with tus.
      if ((0,_isReactNative_js__WEBPACK_IMPORTED_MODULE_0__["default"])() && input && typeof input.uri !== 'undefined') {
        return (0,_uriToBlob_js__WEBPACK_IMPORTED_MODULE_1__["default"])(input.uri).then(function (blob) {
          return new _sources_FileSource_js__WEBPACK_IMPORTED_MODULE_2__["default"](blob);
        })["catch"](function (err) {
          throw new Error("tus: cannot fetch `file.uri` as Blob, make sure the uri is correct and accessible. ".concat(err));
        });
      } // Since we emulate the Blob type in our tests (not all target browsers
      // support it), we cannot use `instanceof` for testing whether the input value
      // can be handled. Instead, we simply check is the slice() function and the
      // size property are available.


      if (typeof input.slice === 'function' && typeof input.size !== 'undefined') {
        return Promise.resolve(new _sources_FileSource_js__WEBPACK_IMPORTED_MODULE_2__["default"](input));
      }

      if (typeof input.read === 'function') {
        chunkSize = Number(chunkSize);

        if (!Number.isFinite(chunkSize)) {
          return Promise.reject(new Error('cannot create source for stream without a finite value for the `chunkSize` option'));
        }

        return Promise.resolve(new _sources_StreamSource_js__WEBPACK_IMPORTED_MODULE_3__["default"](input, chunkSize));
      }

      return Promise.reject(new Error('source object may only be an instance of File, Blob, or Reader in this environment'));
    }
  }]);

  return FileReader;
}();



/***/ }),

/***/ "./node_modules/tus-js-client/lib.esm/browser/fileSignature.js":
/*!*********************************************************************!*\
  !*** ./node_modules/tus-js-client/lib.esm/browser/fileSignature.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ fingerprint)
/* harmony export */ });
/* harmony import */ var _isReactNative_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./isReactNative.js */ "./node_modules/tus-js-client/lib.esm/browser/isReactNative.js");
 // TODO: Differenciate between input types

/**
 * Generate a fingerprint for a file which will be used the store the endpoint
 *
 * @param {File} file
 * @param {Object} options
 * @param {Function} callback
 */

function fingerprint(file, options) {
  if ((0,_isReactNative_js__WEBPACK_IMPORTED_MODULE_0__["default"])()) {
    return Promise.resolve(reactNativeFingerprint(file, options));
  }

  return Promise.resolve(['tus-br', file.name, file.type, file.size, file.lastModified, options.endpoint].join('-'));
}

function reactNativeFingerprint(file, options) {
  var exifHash = file.exif ? hashCode(JSON.stringify(file.exif)) : 'noexif';
  return ['tus-rn', file.name || 'noname', file.size || 'nosize', exifHash, options.endpoint].join('/');
}

function hashCode(str) {
  /* eslint-disable no-bitwise */
  // from https://stackoverflow.com/a/8831937/151666
  var hash = 0;

  if (str.length === 0) {
    return hash;
  }

  for (var i = 0; i < str.length; i++) {
    var _char = str.charCodeAt(i);

    hash = (hash << 5) - hash + _char;
    hash &= hash; // Convert to 32bit integer
  }

  return hash;
}

/***/ }),

/***/ "./node_modules/tus-js-client/lib.esm/browser/httpStack.js":
/*!*****************************************************************!*\
  !*** ./node_modules/tus-js-client/lib.esm/browser/httpStack.js ***!
  \*****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ XHRHttpStack)
/* harmony export */ });
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

/* eslint-disable max-classes-per-file */
var XHRHttpStack = /*#__PURE__*/function () {
  function XHRHttpStack() {
    _classCallCheck(this, XHRHttpStack);
  }

  _createClass(XHRHttpStack, [{
    key: "createRequest",
    value: function createRequest(method, url) {
      return new Request(method, url);
    }
  }, {
    key: "getName",
    value: function getName() {
      return 'XHRHttpStack';
    }
  }]);

  return XHRHttpStack;
}();



var Request = /*#__PURE__*/function () {
  function Request(method, url) {
    _classCallCheck(this, Request);

    this._xhr = new XMLHttpRequest();

    this._xhr.open(method, url, true);

    this._method = method;
    this._url = url;
    this._headers = {};
  }

  _createClass(Request, [{
    key: "getMethod",
    value: function getMethod() {
      return this._method;
    }
  }, {
    key: "getURL",
    value: function getURL() {
      return this._url;
    }
  }, {
    key: "setHeader",
    value: function setHeader(header, value) {
      this._xhr.setRequestHeader(header, value);

      this._headers[header] = value;
    }
  }, {
    key: "getHeader",
    value: function getHeader(header) {
      return this._headers[header];
    }
  }, {
    key: "setProgressHandler",
    value: function setProgressHandler(progressHandler) {
      // Test support for progress events before attaching an event listener
      if (!('upload' in this._xhr)) {
        return;
      }

      this._xhr.upload.onprogress = function (e) {
        if (!e.lengthComputable) {
          return;
        }

        progressHandler(e.loaded);
      };
    }
  }, {
    key: "send",
    value: function send() {
      var _this = this;

      var body = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
      return new Promise(function (resolve, reject) {
        _this._xhr.onload = function () {
          resolve(new Response(_this._xhr));
        };

        _this._xhr.onerror = function (err) {
          reject(err);
        };

        _this._xhr.send(body);
      });
    }
  }, {
    key: "abort",
    value: function abort() {
      this._xhr.abort();

      return Promise.resolve();
    }
  }, {
    key: "getUnderlyingObject",
    value: function getUnderlyingObject() {
      return this._xhr;
    }
  }]);

  return Request;
}();

var Response = /*#__PURE__*/function () {
  function Response(xhr) {
    _classCallCheck(this, Response);

    this._xhr = xhr;
  }

  _createClass(Response, [{
    key: "getStatus",
    value: function getStatus() {
      return this._xhr.status;
    }
  }, {
    key: "getHeader",
    value: function getHeader(header) {
      return this._xhr.getResponseHeader(header);
    }
  }, {
    key: "getBody",
    value: function getBody() {
      return this._xhr.responseText;
    }
  }, {
    key: "getUnderlyingObject",
    value: function getUnderlyingObject() {
      return this._xhr;
    }
  }]);

  return Response;
}();

/***/ }),

/***/ "./node_modules/tus-js-client/lib.esm/browser/index.js":
/*!*************************************************************!*\
  !*** ./node_modules/tus-js-client/lib.esm/browser/index.js ***!
  \*************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   DefaultHttpStack: () => (/* reexport safe */ _httpStack_js__WEBPACK_IMPORTED_MODULE_5__["default"]),
/* harmony export */   DetailedError: () => (/* reexport safe */ _error_js__WEBPACK_IMPORTED_MODULE_3__["default"]),
/* harmony export */   Upload: () => (/* binding */ Upload),
/* harmony export */   canStoreURLs: () => (/* reexport safe */ _urlStorage_js__WEBPACK_IMPORTED_MODULE_4__.canStoreURLs),
/* harmony export */   defaultOptions: () => (/* binding */ defaultOptions),
/* harmony export */   enableDebugLog: () => (/* reexport safe */ _logger_js__WEBPACK_IMPORTED_MODULE_2__.enableDebugLog),
/* harmony export */   isSupported: () => (/* binding */ isSupported)
/* harmony export */ });
/* harmony import */ var _upload_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../upload.js */ "./node_modules/tus-js-client/lib.esm/upload.js");
/* harmony import */ var _noopUrlStorage_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../noopUrlStorage.js */ "./node_modules/tus-js-client/lib.esm/noopUrlStorage.js");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../logger.js */ "./node_modules/tus-js-client/lib.esm/logger.js");
/* harmony import */ var _error_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../error.js */ "./node_modules/tus-js-client/lib.esm/error.js");
/* harmony import */ var _urlStorage_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./urlStorage.js */ "./node_modules/tus-js-client/lib.esm/browser/urlStorage.js");
/* harmony import */ var _httpStack_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./httpStack.js */ "./node_modules/tus-js-client/lib.esm/browser/httpStack.js");
/* harmony import */ var _fileReader_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./fileReader.js */ "./node_modules/tus-js-client/lib.esm/browser/fileReader.js");
/* harmony import */ var _fileSignature_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./fileSignature.js */ "./node_modules/tus-js-client/lib.esm/browser/fileSignature.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }










var defaultOptions = _objectSpread(_objectSpread({}, _upload_js__WEBPACK_IMPORTED_MODULE_0__["default"].defaultOptions), {}, {
  httpStack: new _httpStack_js__WEBPACK_IMPORTED_MODULE_5__["default"](),
  fileReader: new _fileReader_js__WEBPACK_IMPORTED_MODULE_6__["default"](),
  urlStorage: _urlStorage_js__WEBPACK_IMPORTED_MODULE_4__.canStoreURLs ? new _urlStorage_js__WEBPACK_IMPORTED_MODULE_4__.WebStorageUrlStorage() : new _noopUrlStorage_js__WEBPACK_IMPORTED_MODULE_1__["default"](),
  fingerprint: _fileSignature_js__WEBPACK_IMPORTED_MODULE_7__["default"]
});

var Upload = /*#__PURE__*/function (_BaseUpload) {
  _inherits(Upload, _BaseUpload);

  var _super = _createSuper(Upload);

  function Upload() {
    var file = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
    var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

    _classCallCheck(this, Upload);

    options = _objectSpread(_objectSpread({}, defaultOptions), options);
    return _super.call(this, file, options);
  }

  _createClass(Upload, null, [{
    key: "terminate",
    value: function terminate(url) {
      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
      options = _objectSpread(_objectSpread({}, defaultOptions), options);
      return _upload_js__WEBPACK_IMPORTED_MODULE_0__["default"].terminate(url, options);
    }
  }]);

  return Upload;
}(_upload_js__WEBPACK_IMPORTED_MODULE_0__["default"]);

var _window = window,
    XMLHttpRequest = _window.XMLHttpRequest,
    Blob = _window.Blob;
var isSupported = XMLHttpRequest && Blob && typeof Blob.prototype.slice === 'function';


/***/ }),

/***/ "./node_modules/tus-js-client/lib.esm/browser/isReactNative.js":
/*!*********************************************************************!*\
  !*** ./node_modules/tus-js-client/lib.esm/browser/isReactNative.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
var isReactNative = function isReactNative() {
  return typeof navigator !== 'undefined' && typeof navigator.product === 'string' && navigator.product.toLowerCase() === 'reactnative';
};

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (isReactNative);

/***/ }),

/***/ "./node_modules/tus-js-client/lib.esm/browser/sources/FileSource.js":
/*!**************************************************************************!*\
  !*** ./node_modules/tus-js-client/lib.esm/browser/sources/FileSource.js ***!
  \**************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ FileSource)
/* harmony export */ });
/* harmony import */ var _isCordova_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./isCordova.js */ "./node_modules/tus-js-client/lib.esm/browser/sources/isCordova.js");
/* harmony import */ var _readAsByteArray_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./readAsByteArray.js */ "./node_modules/tus-js-client/lib.esm/browser/sources/readAsByteArray.js");
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }




var FileSource = /*#__PURE__*/function () {
  // Make this.size a method
  function FileSource(file) {
    _classCallCheck(this, FileSource);

    this._file = file;
    this.size = file.size;
  }

  _createClass(FileSource, [{
    key: "slice",
    value: function slice(start, end) {
      // In Apache Cordova applications, a File must be resolved using
      // FileReader instances, see
      // https://cordova.apache.org/docs/en/8.x/reference/cordova-plugin-file/index.html#read-a-file
      if ((0,_isCordova_js__WEBPACK_IMPORTED_MODULE_0__["default"])()) {
        return (0,_readAsByteArray_js__WEBPACK_IMPORTED_MODULE_1__["default"])(this._file.slice(start, end));
      }

      var value = this._file.slice(start, end);

      return Promise.resolve({
        value: value
      });
    }
  }, {
    key: "close",
    value: function close() {// Nothing to do here since we don't need to release any resources.
    }
  }]);

  return FileSource;
}();



/***/ }),

/***/ "./node_modules/tus-js-client/lib.esm/browser/sources/StreamSource.js":
/*!****************************************************************************!*\
  !*** ./node_modules/tus-js-client/lib.esm/browser/sources/StreamSource.js ***!
  \****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ StreamSource)
/* harmony export */ });
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function len(blobOrArray) {
  if (blobOrArray === undefined) return 0;
  if (blobOrArray.size !== undefined) return blobOrArray.size;
  return blobOrArray.length;
}
/*
  Typed arrays and blobs don't have a concat method.
  This function helps StreamSource accumulate data to reach chunkSize.
*/


function concat(a, b) {
  if (a.concat) {
    // Is `a` an Array?
    return a.concat(b);
  }

  if (a instanceof Blob) {
    return new Blob([a, b], {
      type: a.type
    });
  }

  if (a.set) {
    // Is `a` a typed array?
    var c = new a.constructor(a.length + b.length);
    c.set(a);
    c.set(b, a.length);
    return c;
  }

  throw new Error('Unknown data type');
}

var StreamSource = /*#__PURE__*/function () {
  function StreamSource(reader) {
    _classCallCheck(this, StreamSource);

    this._buffer = undefined;
    this._bufferOffset = 0;
    this._reader = reader;
    this._done = false;
  }

  _createClass(StreamSource, [{
    key: "slice",
    value: function slice(start, end) {
      if (start < this._bufferOffset) {
        return Promise.reject(new Error("Requested data is before the reader's current offset"));
      }

      return this._readUntilEnoughDataOrDone(start, end);
    }
  }, {
    key: "_readUntilEnoughDataOrDone",
    value: function _readUntilEnoughDataOrDone(start, end) {
      var _this = this;

      var hasEnoughData = end <= this._bufferOffset + len(this._buffer);

      if (this._done || hasEnoughData) {
        var value = this._getDataFromBuffer(start, end);

        var done = value == null ? this._done : false;
        return Promise.resolve({
          value: value,
          done: done
        });
      }

      return this._reader.read().then(function (_ref) {
        var value = _ref.value,
            done = _ref.done;

        if (done) {
          _this._done = true;
        } else if (_this._buffer === undefined) {
          _this._buffer = value;
        } else {
          _this._buffer = concat(_this._buffer, value);
        }

        return _this._readUntilEnoughDataOrDone(start, end);
      });
    }
  }, {
    key: "_getDataFromBuffer",
    value: function _getDataFromBuffer(start, end) {
      // Remove data from buffer before `start`.
      // Data might be reread from the buffer if an upload fails, so we can only
      // safely delete data when it comes *before* what is currently being read.
      if (start > this._bufferOffset) {
        this._buffer = this._buffer.slice(start - this._bufferOffset);
        this._bufferOffset = start;
      } // If the buffer is empty after removing old data, all data has been read.


      var hasAllDataBeenRead = len(this._buffer) === 0;

      if (this._done && hasAllDataBeenRead) {
        return null;
      } // We already removed data before `start`, so we just return the first
      // chunk from the buffer.


      return this._buffer.slice(0, end - start);
    }
  }, {
    key: "close",
    value: function close() {
      if (this._reader.cancel) {
        this._reader.cancel();
      }
    }
  }]);

  return StreamSource;
}();



/***/ }),

/***/ "./node_modules/tus-js-client/lib.esm/browser/sources/isCordova.js":
/*!*************************************************************************!*\
  !*** ./node_modules/tus-js-client/lib.esm/browser/sources/isCordova.js ***!
  \*************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
var isCordova = function isCordova() {
  return typeof window !== 'undefined' && (typeof window.PhoneGap !== 'undefined' || typeof window.Cordova !== 'undefined' || typeof window.cordova !== 'undefined');
};

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (isCordova);

/***/ }),

/***/ "./node_modules/tus-js-client/lib.esm/browser/sources/readAsByteArray.js":
/*!*******************************************************************************!*\
  !*** ./node_modules/tus-js-client/lib.esm/browser/sources/readAsByteArray.js ***!
  \*******************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ readAsByteArray)
/* harmony export */ });
/**
 * readAsByteArray converts a File object to a Uint8Array.
 * This function is only used on the Apache Cordova platform.
 * See https://cordova.apache.org/docs/en/latest/reference/cordova-plugin-file/index.html#read-a-file
 */
function readAsByteArray(chunk) {
  return new Promise(function (resolve, reject) {
    var reader = new FileReader();

    reader.onload = function () {
      var value = new Uint8Array(reader.result);
      resolve({
        value: value
      });
    };

    reader.onerror = function (err) {
      reject(err);
    };

    reader.readAsArrayBuffer(chunk);
  });
}

/***/ }),

/***/ "./node_modules/tus-js-client/lib.esm/browser/uriToBlob.js":
/*!*****************************************************************!*\
  !*** ./node_modules/tus-js-client/lib.esm/browser/uriToBlob.js ***!
  \*****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ uriToBlob)
/* harmony export */ });
/**
 * uriToBlob resolves a URI to a Blob object. This is used for
 * React Native to retrieve a file (identified by a file://
 * URI) as a blob.
 */
function uriToBlob(uri) {
  return new Promise(function (resolve, reject) {
    var xhr = new XMLHttpRequest();
    xhr.responseType = 'blob';

    xhr.onload = function () {
      var blob = xhr.response;
      resolve(blob);
    };

    xhr.onerror = function (err) {
      reject(err);
    };

    xhr.open('GET', uri);
    xhr.send();
  });
}

/***/ }),

/***/ "./node_modules/tus-js-client/lib.esm/browser/urlStorage.js":
/*!******************************************************************!*\
  !*** ./node_modules/tus-js-client/lib.esm/browser/urlStorage.js ***!
  \******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   WebStorageUrlStorage: () => (/* binding */ WebStorageUrlStorage),
/* harmony export */   canStoreURLs: () => (/* binding */ canStoreURLs)
/* harmony export */ });
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

var hasStorage = false;

try {
  hasStorage = 'localStorage' in window; // Attempt to store and read entries from the local storage to detect Private
  // Mode on Safari on iOS (see #49)
  // If the key was not used before, we remove it from local storage again to
  // not cause confusion where the entry came from.

  var key = 'tusSupport';
  var originalValue = localStorage.getItem(key);
  localStorage.setItem(key, originalValue);
  if (originalValue === null) localStorage.removeItem(key);
} catch (e) {
  // If we try to access localStorage inside a sandboxed iframe, a SecurityError
  // is thrown. When in private mode on iOS Safari, a QuotaExceededError is
  // thrown (see #49)
  if (e.code === e.SECURITY_ERR || e.code === e.QUOTA_EXCEEDED_ERR) {
    hasStorage = false;
  } else {
    throw e;
  }
}

var canStoreURLs = hasStorage;
var WebStorageUrlStorage = /*#__PURE__*/function () {
  function WebStorageUrlStorage() {
    _classCallCheck(this, WebStorageUrlStorage);
  }

  _createClass(WebStorageUrlStorage, [{
    key: "findAllUploads",
    value: function findAllUploads() {
      var results = this._findEntries('tus::');

      return Promise.resolve(results);
    }
  }, {
    key: "findUploadsByFingerprint",
    value: function findUploadsByFingerprint(fingerprint) {
      var results = this._findEntries("tus::".concat(fingerprint, "::"));

      return Promise.resolve(results);
    }
  }, {
    key: "removeUpload",
    value: function removeUpload(urlStorageKey) {
      localStorage.removeItem(urlStorageKey);
      return Promise.resolve();
    }
  }, {
    key: "addUpload",
    value: function addUpload(fingerprint, upload) {
      var id = Math.round(Math.random() * 1e12);
      var key = "tus::".concat(fingerprint, "::").concat(id);
      localStorage.setItem(key, JSON.stringify(upload));
      return Promise.resolve(key);
    }
  }, {
    key: "_findEntries",
    value: function _findEntries(prefix) {
      var results = [];

      for (var i = 0; i < localStorage.length; i++) {
        var _key = localStorage.key(i);

        if (_key.indexOf(prefix) !== 0) continue;

        try {
          var upload = JSON.parse(localStorage.getItem(_key));
          upload.urlStorageKey = _key;
          results.push(upload);
        } catch (e) {// The JSON parse error is intentionally ignored here, so a malformed
          // entry in the storage cannot prevent an upload.
        }
      }

      return results;
    }
  }]);

  return WebStorageUrlStorage;
}();

/***/ }),

/***/ "./node_modules/tus-js-client/lib.esm/error.js":
/*!*****************************************************!*\
  !*** ./node_modules/tus-js-client/lib.esm/error.js ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _wrapNativeSuper(Class) { var _cache = typeof Map === "function" ? new Map() : undefined; _wrapNativeSuper = function _wrapNativeSuper(Class) { if (Class === null || !_isNativeFunction(Class)) return Class; if (typeof Class !== "function") { throw new TypeError("Super expression must either be null or a function"); } if (typeof _cache !== "undefined") { if (_cache.has(Class)) return _cache.get(Class); _cache.set(Class, Wrapper); } function Wrapper() { return _construct(Class, arguments, _getPrototypeOf(this).constructor); } Wrapper.prototype = Object.create(Class.prototype, { constructor: { value: Wrapper, enumerable: false, writable: true, configurable: true } }); return _setPrototypeOf(Wrapper, Class); }; return _wrapNativeSuper(Class); }

function _construct(Parent, args, Class) { if (_isNativeReflectConstruct()) { _construct = Reflect.construct.bind(); } else { _construct = function _construct(Parent, args, Class) { var a = [null]; a.push.apply(a, args); var Constructor = Function.bind.apply(Parent, a); var instance = new Constructor(); if (Class) _setPrototypeOf(instance, Class.prototype); return instance; }; } return _construct.apply(null, arguments); }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _isNativeFunction(fn) { return Function.toString.call(fn).indexOf("[native code]") !== -1; }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

var DetailedError = /*#__PURE__*/function (_Error) {
  _inherits(DetailedError, _Error);

  var _super = _createSuper(DetailedError);

  function DetailedError(message) {
    var _this;

    var causingErr = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
    var req = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;
    var res = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : null;

    _classCallCheck(this, DetailedError);

    _this = _super.call(this, message);
    _this.originalRequest = req;
    _this.originalResponse = res;
    _this.causingError = causingErr;

    if (causingErr != null) {
      message += ", caused by ".concat(causingErr.toString());
    }

    if (req != null) {
      var requestId = req.getHeader('X-Request-ID') || 'n/a';
      var method = req.getMethod();
      var url = req.getURL();
      var status = res ? res.getStatus() : 'n/a';
      var body = res ? res.getBody() || '' : 'n/a';
      message += ", originated from request (method: ".concat(method, ", url: ").concat(url, ", response code: ").concat(status, ", response text: ").concat(body, ", request id: ").concat(requestId, ")");
    }

    _this.message = message;
    return _this;
  }

  return _createClass(DetailedError);
}( /*#__PURE__*/_wrapNativeSuper(Error));

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DetailedError);

/***/ }),

/***/ "./node_modules/tus-js-client/lib.esm/logger.js":
/*!******************************************************!*\
  !*** ./node_modules/tus-js-client/lib.esm/logger.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   enableDebugLog: () => (/* binding */ enableDebugLog),
/* harmony export */   log: () => (/* binding */ log)
/* harmony export */ });
/* eslint no-console: "off" */
var isEnabled = false;
function enableDebugLog() {
  isEnabled = true;
}
function log(msg) {
  if (!isEnabled) return;
  console.log(msg);
}

/***/ }),

/***/ "./node_modules/tus-js-client/lib.esm/noopUrlStorage.js":
/*!**************************************************************!*\
  !*** ./node_modules/tus-js-client/lib.esm/noopUrlStorage.js ***!
  \**************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ NoopUrlStorage)
/* harmony export */ });
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

/* eslint no-unused-vars: "off" */
var NoopUrlStorage = /*#__PURE__*/function () {
  function NoopUrlStorage() {
    _classCallCheck(this, NoopUrlStorage);
  }

  _createClass(NoopUrlStorage, [{
    key: "listAllUploads",
    value: function listAllUploads() {
      return Promise.resolve([]);
    }
  }, {
    key: "findUploadsByFingerprint",
    value: function findUploadsByFingerprint(fingerprint) {
      return Promise.resolve([]);
    }
  }, {
    key: "removeUpload",
    value: function removeUpload(urlStorageKey) {
      return Promise.resolve();
    }
  }, {
    key: "addUpload",
    value: function addUpload(fingerprint, upload) {
      return Promise.resolve(null);
    }
  }]);

  return NoopUrlStorage;
}();



/***/ }),

/***/ "./node_modules/tus-js-client/lib.esm/upload.js":
/*!******************************************************!*\
  !*** ./node_modules/tus-js-client/lib.esm/upload.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var js_base64__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! js-base64 */ "./node_modules/js-base64/base64.mjs");
/* harmony import */ var url_parse__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! url-parse */ "./node_modules/url-parse/index.js");
/* harmony import */ var url_parse__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(url_parse__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _error_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./error.js */ "./node_modules/tus-js-client/lib.esm/error.js");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./logger.js */ "./node_modules/tus-js-client/lib.esm/logger.js");
/* harmony import */ var _uuid_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./uuid.js */ "./node_modules/tus-js-client/lib.esm/uuid.js");
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { var _i = arr == null ? null : typeof Symbol !== "undefined" && arr[Symbol.iterator] || arr["@@iterator"]; if (_i == null) return; var _arr = []; var _n = true; var _d = false; var _s, _e; try { for (_i = _i.call(arr); !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }






var defaultOptions = {
  endpoint: null,
  uploadUrl: null,
  metadata: {},
  fingerprint: null,
  uploadSize: null,
  onProgress: null,
  onChunkComplete: null,
  onSuccess: null,
  onError: null,
  onUploadUrlAvailable: null,
  overridePatchMethod: false,
  headers: {},
  addRequestId: false,
  onBeforeRequest: null,
  onAfterResponse: null,
  onShouldRetry: null,
  chunkSize: Infinity,
  retryDelays: [0, 1000, 3000, 5000],
  parallelUploads: 1,
  parallelUploadBoundaries: null,
  storeFingerprintForResuming: true,
  removeFingerprintOnSuccess: false,
  uploadLengthDeferred: false,
  uploadDataDuringCreation: false,
  urlStorage: null,
  fileReader: null,
  httpStack: null
};

var BaseUpload = /*#__PURE__*/function () {
  function BaseUpload(file, options) {
    _classCallCheck(this, BaseUpload);

    // Warn about removed options from previous versions
    if ('resume' in options) {
      console.log('tus: The `resume` option has been removed in tus-js-client v2. Please use the URL storage API instead.'); // eslint-disable-line no-console
    } // The default options will already be added from the wrapper classes.


    this.options = options; // Cast chunkSize to integer

    this.options.chunkSize = Number(this.options.chunkSize); // The storage module used to store URLs

    this._urlStorage = this.options.urlStorage; // The underlying File/Blob object

    this.file = file; // The URL against which the file will be uploaded

    this.url = null; // The underlying request object for the current PATCH request

    this._req = null; // The fingerpinrt for the current file (set after start())

    this._fingerprint = null; // The key that the URL storage returned when saving an URL with a fingerprint,

    this._urlStorageKey = null; // The offset used in the current PATCH request

    this._offset = null; // True if the current PATCH request has been aborted

    this._aborted = false; // The file's size in bytes

    this._size = null; // The Source object which will wrap around the given file and provides us
    // with a unified interface for getting its size and slice chunks from its
    // content allowing us to easily handle Files, Blobs, Buffers and Streams.

    this._source = null; // The current count of attempts which have been made. Zero indicates none.

    this._retryAttempt = 0; // The timeout's ID which is used to delay the next retry

    this._retryTimeout = null; // The offset of the remote upload before the latest attempt was started.

    this._offsetBeforeRetry = 0; // An array of BaseUpload instances which are used for uploading the different
    // parts, if the parallelUploads option is used.

    this._parallelUploads = null; // An array of upload URLs which are used for uploading the different
    // parts, if the parallelUploads option is used.

    this._parallelUploadUrls = null;
  }
  /**
   * Use the Termination extension to delete an upload from the server by sending a DELETE
   * request to the specified upload URL. This is only possible if the server supports the
   * Termination extension. If the `options.retryDelays` property is set, the method will
   * also retry if an error ocurrs.
   *
   * @param {String} url The upload's URL which will be terminated.
   * @param {object} options Optional options for influencing HTTP requests.
   * @return {Promise} The Promise will be resolved/rejected when the requests finish.
   */


  _createClass(BaseUpload, [{
    key: "findPreviousUploads",
    value: function findPreviousUploads() {
      var _this = this;

      return this.options.fingerprint(this.file, this.options).then(function (fingerprint) {
        return _this._urlStorage.findUploadsByFingerprint(fingerprint);
      });
    }
  }, {
    key: "resumeFromPreviousUpload",
    value: function resumeFromPreviousUpload(previousUpload) {
      this.url = previousUpload.uploadUrl || null;
      this._parallelUploadUrls = previousUpload.parallelUploadUrls || null;
      this._urlStorageKey = previousUpload.urlStorageKey;
    }
  }, {
    key: "start",
    value: function start() {
      var _this2 = this;

      var file = this.file;

      if (!file) {
        this._emitError(new Error('tus: no file or stream to upload provided'));

        return;
      }

      if (!this.options.endpoint && !this.options.uploadUrl && !this.url) {
        this._emitError(new Error('tus: neither an endpoint or an upload URL is provided'));

        return;
      }

      var retryDelays = this.options.retryDelays;

      if (retryDelays != null && Object.prototype.toString.call(retryDelays) !== '[object Array]') {
        this._emitError(new Error('tus: the `retryDelays` option must either be an array or null'));

        return;
      }

      if (this.options.parallelUploads > 1) {
        // Test which options are incompatible with parallel uploads.
        for (var _i = 0, _arr = ['uploadUrl', 'uploadSize', 'uploadLengthDeferred']; _i < _arr.length; _i++) {
          var optionName = _arr[_i];

          if (this.options[optionName]) {
            this._emitError(new Error("tus: cannot use the ".concat(optionName, " option when parallelUploads is enabled")));

            return;
          }
        }
      }

      if (this.options.parallelUploadBoundaries) {
        if (this.options.parallelUploads <= 1) {
          this._emitError(new Error('tus: cannot use the `parallelUploadBoundaries` option when `parallelUploads` is disabled'));

          return;
        }

        if (this.options.parallelUploads !== this.options.parallelUploadBoundaries.length) {
          this._emitError(new Error('tus: the `parallelUploadBoundaries` must have the same length as the value of `parallelUploads`'));

          return;
        }
      }

      this.options.fingerprint(file, this.options).then(function (fingerprint) {
        if (fingerprint == null) {
          (0,_logger_js__WEBPACK_IMPORTED_MODULE_2__.log)('No fingerprint was calculated meaning that the upload cannot be stored in the URL storage.');
        } else {
          (0,_logger_js__WEBPACK_IMPORTED_MODULE_2__.log)("Calculated fingerprint: ".concat(fingerprint));
        }

        _this2._fingerprint = fingerprint;

        if (_this2._source) {
          return _this2._source;
        }

        return _this2.options.fileReader.openFile(file, _this2.options.chunkSize);
      }).then(function (source) {
        _this2._source = source; // First, we look at the uploadLengthDeferred option.
        // Next, we check if the caller has supplied a manual upload size.
        // Finally, we try to use the calculated size from the source object.

        if (_this2.options.uploadLengthDeferred) {
          _this2._size = null;
        } else if (_this2.options.uploadSize != null) {
          _this2._size = Number(_this2.options.uploadSize);

          if (Number.isNaN(_this2._size)) {
            _this2._emitError(new Error('tus: cannot convert `uploadSize` option into a number'));

            return;
          }
        } else {
          _this2._size = _this2._source.size;

          if (_this2._size == null) {
            _this2._emitError(new Error("tus: cannot automatically derive upload's size from input. Specify it manually using the `uploadSize` option or use the `uploadLengthDeferred` option"));

            return;
          }
        } // If the upload was configured to use multiple requests or if we resume from
        // an upload which used multiple requests, we start a parallel upload.


        if (_this2.options.parallelUploads > 1 || _this2._parallelUploadUrls != null) {
          _this2._startParallelUpload();
        } else {
          _this2._startSingleUpload();
        }
      })["catch"](function (err) {
        _this2._emitError(err);
      });
    }
    /**
     * Initiate the uploading procedure for a parallelized upload, where one file is split into
     * multiple request which are run in parallel.
     *
     * @api private
     */

  }, {
    key: "_startParallelUpload",
    value: function _startParallelUpload() {
      var _this$options$paralle,
          _this3 = this;

      var totalSize = this._size;
      var totalProgress = 0;
      this._parallelUploads = [];
      var partCount = this._parallelUploadUrls != null ? this._parallelUploadUrls.length : this.options.parallelUploads; // The input file will be split into multiple slices which are uploaded in separate
      // requests. Here we get the start and end position for the slices.

      var parts = (_this$options$paralle = this.options.parallelUploadBoundaries) !== null && _this$options$paralle !== void 0 ? _this$options$paralle : splitSizeIntoParts(this._source.size, partCount); // Attach URLs from previous uploads, if available.

      if (this._parallelUploadUrls) {
        parts.forEach(function (part, index) {
          part.uploadUrl = _this3._parallelUploadUrls[index] || null;
        });
      } // Create an empty list for storing the upload URLs


      this._parallelUploadUrls = new Array(parts.length); // Generate a promise for each slice that will be resolve if the respective
      // upload is completed.

      var uploads = parts.map(function (part, index) {
        var lastPartProgress = 0;
        return _this3._source.slice(part.start, part.end).then(function (_ref) {
          var value = _ref.value;
          return new Promise(function (resolve, reject) {
            // Merge with the user supplied options but overwrite some values.
            var options = _objectSpread(_objectSpread({}, _this3.options), {}, {
              // If available, the partial upload should be resumed from a previous URL.
              uploadUrl: part.uploadUrl || null,
              // We take manually care of resuming for partial uploads, so they should
              // not be stored in the URL storage.
              storeFingerprintForResuming: false,
              removeFingerprintOnSuccess: false,
              // Reset the parallelUploads option to not cause recursion.
              parallelUploads: 1,
              // Reset this option as we are not doing a parallel upload.
              parallelUploadBoundaries: null,
              metadata: {},
              // Add the header to indicate the this is a partial upload.
              headers: _objectSpread(_objectSpread({}, _this3.options.headers), {}, {
                'Upload-Concat': 'partial'
              }),
              // Reject or resolve the promise if the upload errors or completes.
              onSuccess: resolve,
              onError: reject,
              // Based in the progress for this partial upload, calculate the progress
              // for the entire final upload.
              onProgress: function onProgress(newPartProgress) {
                totalProgress = totalProgress - lastPartProgress + newPartProgress;
                lastPartProgress = newPartProgress;

                _this3._emitProgress(totalProgress, totalSize);
              },
              // Wait until every partial upload has an upload URL, so we can add
              // them to the URL storage.
              onUploadUrlAvailable: function onUploadUrlAvailable() {
                _this3._parallelUploadUrls[index] = upload.url; // Test if all uploads have received an URL

                if (_this3._parallelUploadUrls.filter(function (u) {
                  return Boolean(u);
                }).length === parts.length) {
                  _this3._saveUploadInUrlStorage();
                }
              }
            });

            var upload = new BaseUpload(value, options);
            upload.start(); // Store the upload in an array, so we can later abort them if necessary.

            _this3._parallelUploads.push(upload);
          });
        });
      });
      var req; // Wait until all partial uploads are finished and we can send the POST request for
      // creating the final upload.

      Promise.all(uploads).then(function () {
        req = _this3._openRequest('POST', _this3.options.endpoint);
        req.setHeader('Upload-Concat', "final;".concat(_this3._parallelUploadUrls.join(' '))); // Add metadata if values have been added

        var metadata = encodeMetadata(_this3.options.metadata);

        if (metadata !== '') {
          req.setHeader('Upload-Metadata', metadata);
        }

        return _this3._sendRequest(req, null);
      }).then(function (res) {
        if (!inStatusCategory(res.getStatus(), 200)) {
          _this3._emitHttpError(req, res, 'tus: unexpected response while creating upload');

          return;
        }

        var location = res.getHeader('Location');

        if (location == null) {
          _this3._emitHttpError(req, res, 'tus: invalid or missing Location header');

          return;
        }

        _this3.url = resolveUrl(_this3.options.endpoint, location);
        (0,_logger_js__WEBPACK_IMPORTED_MODULE_2__.log)("Created upload at ".concat(_this3.url));

        _this3._emitSuccess();
      })["catch"](function (err) {
        _this3._emitError(err);
      });
    }
    /**
     * Initiate the uploading procedure for a non-parallel upload. Here the entire file is
     * uploaded in a sequential matter.
     *
     * @api private
     */

  }, {
    key: "_startSingleUpload",
    value: function _startSingleUpload() {
      // Reset the aborted flag when the upload is started or else the
      // _performUpload will stop before sending a request if the upload has been
      // aborted previously.
      this._aborted = false; // The upload had been started previously and we should reuse this URL.

      if (this.url != null) {
        (0,_logger_js__WEBPACK_IMPORTED_MODULE_2__.log)("Resuming upload from previous URL: ".concat(this.url));

        this._resumeUpload();

        return;
      } // A URL has manually been specified, so we try to resume


      if (this.options.uploadUrl != null) {
        (0,_logger_js__WEBPACK_IMPORTED_MODULE_2__.log)("Resuming upload from provided URL: ".concat(this.options.uploadUrl));
        this.url = this.options.uploadUrl;

        this._resumeUpload();

        return;
      } // An upload has not started for the file yet, so we start a new one


      (0,_logger_js__WEBPACK_IMPORTED_MODULE_2__.log)('Creating a new upload');

      this._createUpload();
    }
    /**
     * Abort any running request and stop the current upload. After abort is called, no event
     * handler will be invoked anymore. You can use the `start` method to resume the upload
     * again.
     * If `shouldTerminate` is true, the `terminate` function will be called to remove the
     * current upload from the server.
     *
     * @param {boolean} shouldTerminate True if the upload should be deleted from the server.
     * @return {Promise} The Promise will be resolved/rejected when the requests finish.
     */

  }, {
    key: "abort",
    value: function abort(shouldTerminate) {
      var _this4 = this;

      // Stop any parallel partial uploads, that have been started in _startParallelUploads.
      if (this._parallelUploads != null) {
        this._parallelUploads.forEach(function (upload) {
          upload.abort(shouldTerminate);
        });
      } // Stop any current running request.


      if (this._req !== null) {
        this._req.abort(); // Note: We do not close the file source here, so the user can resume in the future.

      }

      this._aborted = true; // Stop any timeout used for initiating a retry.

      if (this._retryTimeout != null) {
        clearTimeout(this._retryTimeout);
        this._retryTimeout = null;
      }

      if (!shouldTerminate || this.url == null) {
        return Promise.resolve();
      }

      return BaseUpload.terminate(this.url, this.options) // Remove entry from the URL storage since the upload URL is no longer valid.
      .then(function () {
        return _this4._removeFromUrlStorage();
      });
    }
  }, {
    key: "_emitHttpError",
    value: function _emitHttpError(req, res, message, causingErr) {
      this._emitError(new _error_js__WEBPACK_IMPORTED_MODULE_1__["default"](message, causingErr, req, res));
    }
  }, {
    key: "_emitError",
    value: function _emitError(err) {
      var _this5 = this;

      // Do not emit errors, e.g. from aborted HTTP requests, if the upload has been stopped.
      if (this._aborted) return; // Check if we should retry, when enabled, before sending the error to the user.

      if (this.options.retryDelays != null) {
        // We will reset the attempt counter if
        // - we were already able to connect to the server (offset != null) and
        // - we were able to upload a small chunk of data to the server
        var shouldResetDelays = this._offset != null && this._offset > this._offsetBeforeRetry;

        if (shouldResetDelays) {
          this._retryAttempt = 0;
        }

        if (shouldRetry(err, this._retryAttempt, this.options)) {
          var delay = this.options.retryDelays[this._retryAttempt++];
          this._offsetBeforeRetry = this._offset;
          this._retryTimeout = setTimeout(function () {
            _this5.start();
          }, delay);
          return;
        }
      }

      if (typeof this.options.onError === 'function') {
        this.options.onError(err);
      } else {
        throw err;
      }
    }
    /**
     * Publishes notification if the upload has been successfully completed.
     *
     * @api private
     */

  }, {
    key: "_emitSuccess",
    value: function _emitSuccess() {
      if (this.options.removeFingerprintOnSuccess) {
        // Remove stored fingerprint and corresponding endpoint. This causes
        // new uploads of the same file to be treated as a different file.
        this._removeFromUrlStorage();
      }

      if (typeof this.options.onSuccess === 'function') {
        this.options.onSuccess();
      }
    }
    /**
     * Publishes notification when data has been sent to the server. This
     * data may not have been accepted by the server yet.
     *
     * @param {number} bytesSent  Number of bytes sent to the server.
     * @param {number} bytesTotal Total number of bytes to be sent to the server.
     * @api private
     */

  }, {
    key: "_emitProgress",
    value: function _emitProgress(bytesSent, bytesTotal) {
      if (typeof this.options.onProgress === 'function') {
        this.options.onProgress(bytesSent, bytesTotal);
      }
    }
    /**
     * Publishes notification when a chunk of data has been sent to the server
     * and accepted by the server.
     * @param {number} chunkSize  Size of the chunk that was accepted by the server.
     * @param {number} bytesAccepted Total number of bytes that have been
     *                                accepted by the server.
     * @param {number} bytesTotal Total number of bytes to be sent to the server.
     * @api private
     */

  }, {
    key: "_emitChunkComplete",
    value: function _emitChunkComplete(chunkSize, bytesAccepted, bytesTotal) {
      if (typeof this.options.onChunkComplete === 'function') {
        this.options.onChunkComplete(chunkSize, bytesAccepted, bytesTotal);
      }
    }
    /**
     * Create a new upload using the creation extension by sending a POST
     * request to the endpoint. After successful creation the file will be
     * uploaded
     *
     * @api private
     */

  }, {
    key: "_createUpload",
    value: function _createUpload() {
      var _this6 = this;

      if (!this.options.endpoint) {
        this._emitError(new Error('tus: unable to create upload because no endpoint is provided'));

        return;
      }

      var req = this._openRequest('POST', this.options.endpoint);

      if (this.options.uploadLengthDeferred) {
        req.setHeader('Upload-Defer-Length', 1);
      } else {
        req.setHeader('Upload-Length', this._size);
      } // Add metadata if values have been added


      var metadata = encodeMetadata(this.options.metadata);

      if (metadata !== '') {
        req.setHeader('Upload-Metadata', metadata);
      }

      var promise;

      if (this.options.uploadDataDuringCreation && !this.options.uploadLengthDeferred) {
        this._offset = 0;
        promise = this._addChunkToRequest(req);
      } else {
        promise = this._sendRequest(req, null);
      }

      promise.then(function (res) {
        if (!inStatusCategory(res.getStatus(), 200)) {
          _this6._emitHttpError(req, res, 'tus: unexpected response while creating upload');

          return;
        }

        var location = res.getHeader('Location');

        if (location == null) {
          _this6._emitHttpError(req, res, 'tus: invalid or missing Location header');

          return;
        }

        _this6.url = resolveUrl(_this6.options.endpoint, location);
        (0,_logger_js__WEBPACK_IMPORTED_MODULE_2__.log)("Created upload at ".concat(_this6.url));

        if (typeof _this6.options.onUploadUrlAvailable === 'function') {
          _this6.options.onUploadUrlAvailable();
        }

        if (_this6._size === 0) {
          // Nothing to upload and file was successfully created
          _this6._emitSuccess();

          _this6._source.close();

          return;
        }

        _this6._saveUploadInUrlStorage().then(function () {
          if (_this6.options.uploadDataDuringCreation) {
            _this6._handleUploadResponse(req, res);
          } else {
            _this6._offset = 0;

            _this6._performUpload();
          }
        });
      })["catch"](function (err) {
        _this6._emitHttpError(req, null, 'tus: failed to create upload', err);
      });
    }
    /*
     * Try to resume an existing upload. First a HEAD request will be sent
     * to retrieve the offset. If the request fails a new upload will be
     * created. In the case of a successful response the file will be uploaded.
     *
     * @api private
     */

  }, {
    key: "_resumeUpload",
    value: function _resumeUpload() {
      var _this7 = this;

      var req = this._openRequest('HEAD', this.url);

      var promise = this._sendRequest(req, null);

      promise.then(function (res) {
        var status = res.getStatus();

        if (!inStatusCategory(status, 200)) {
          // If the upload is locked (indicated by the 423 Locked status code), we
          // emit an error instead of directly starting a new upload. This way the
          // retry logic can catch the error and will retry the upload. An upload
          // is usually locked for a short period of time and will be available
          // afterwards.
          if (status === 423) {
            _this7._emitHttpError(req, res, 'tus: upload is currently locked; retry later');

            return;
          }

          if (inStatusCategory(status, 400)) {
            // Remove stored fingerprint and corresponding endpoint,
            // on client errors since the file can not be found
            _this7._removeFromUrlStorage();
          }

          if (!_this7.options.endpoint) {
            // Don't attempt to create a new upload if no endpoint is provided.
            _this7._emitHttpError(req, res, 'tus: unable to resume upload (new upload cannot be created without an endpoint)');

            return;
          } // Try to create a new upload


          _this7.url = null;

          _this7._createUpload();

          return;
        }

        var offset = parseInt(res.getHeader('Upload-Offset'), 10);

        if (Number.isNaN(offset)) {
          _this7._emitHttpError(req, res, 'tus: invalid or missing offset value');

          return;
        }

        var length = parseInt(res.getHeader('Upload-Length'), 10);

        if (Number.isNaN(length) && !_this7.options.uploadLengthDeferred) {
          _this7._emitHttpError(req, res, 'tus: invalid or missing length value');

          return;
        }

        if (typeof _this7.options.onUploadUrlAvailable === 'function') {
          _this7.options.onUploadUrlAvailable();
        }

        _this7._saveUploadInUrlStorage().then(function () {
          // Upload has already been completed and we do not need to send additional
          // data to the server
          if (offset === length) {
            _this7._emitProgress(length, length);

            _this7._emitSuccess();

            return;
          }

          _this7._offset = offset;

          _this7._performUpload();
        });
      })["catch"](function (err) {
        _this7._emitHttpError(req, null, 'tus: failed to resume upload', err);
      });
    }
    /**
     * Start uploading the file using PATCH requests. The file will be divided
     * into chunks as specified in the chunkSize option. During the upload
     * the onProgress event handler may be invoked multiple times.
     *
     * @api private
     */

  }, {
    key: "_performUpload",
    value: function _performUpload() {
      var _this8 = this;

      // If the upload has been aborted, we will not send the next PATCH request.
      // This is important if the abort method was called during a callback, such
      // as onChunkComplete or onProgress.
      if (this._aborted) {
        return;
      }

      var req; // Some browser and servers may not support the PATCH method. For those
      // cases, you can tell tus-js-client to use a POST request with the
      // X-HTTP-Method-Override header for simulating a PATCH request.

      if (this.options.overridePatchMethod) {
        req = this._openRequest('POST', this.url);
        req.setHeader('X-HTTP-Method-Override', 'PATCH');
      } else {
        req = this._openRequest('PATCH', this.url);
      }

      req.setHeader('Upload-Offset', this._offset);

      var promise = this._addChunkToRequest(req);

      promise.then(function (res) {
        if (!inStatusCategory(res.getStatus(), 200)) {
          _this8._emitHttpError(req, res, 'tus: unexpected response while uploading chunk');

          return;
        }

        _this8._handleUploadResponse(req, res);
      })["catch"](function (err) {
        // Don't emit an error if the upload was aborted manually
        if (_this8._aborted) {
          return;
        }

        _this8._emitHttpError(req, null, "tus: failed to upload chunk at offset ".concat(_this8._offset), err);
      });
    }
    /**
     * _addChunktoRequest reads a chunk from the source and sends it using the
     * supplied request object. It will not handle the response.
     *
     * @api private
     */

  }, {
    key: "_addChunkToRequest",
    value: function _addChunkToRequest(req) {
      var _this9 = this;

      var start = this._offset;
      var end = this._offset + this.options.chunkSize;
      req.setProgressHandler(function (bytesSent) {
        _this9._emitProgress(start + bytesSent, _this9._size);
      });
      req.setHeader('Content-Type', 'application/offset+octet-stream'); // The specified chunkSize may be Infinity or the calcluated end position
      // may exceed the file's size. In both cases, we limit the end position to
      // the input's total size for simpler calculations and correctness.

      if ((end === Infinity || end > this._size) && !this.options.uploadLengthDeferred) {
        end = this._size;
      }

      return this._source.slice(start, end).then(function (_ref2) {
        var value = _ref2.value,
            done = _ref2.done;

        // If the upload length is deferred, the upload size was not specified during
        // upload creation. So, if the file reader is done reading, we know the total
        // upload size and can tell the tus server.
        if (_this9.options.uploadLengthDeferred && done) {
          _this9._size = _this9._offset + (value && value.size ? value.size : 0);
          req.setHeader('Upload-Length', _this9._size);
        }

        if (value === null) {
          return _this9._sendRequest(req);
        }

        _this9._emitProgress(_this9._offset, _this9._size);

        return _this9._sendRequest(req, value);
      });
    }
    /**
     * _handleUploadResponse is used by requests that haven been sent using _addChunkToRequest
     * and already have received a response.
     *
     * @api private
     */

  }, {
    key: "_handleUploadResponse",
    value: function _handleUploadResponse(req, res) {
      var offset = parseInt(res.getHeader('Upload-Offset'), 10);

      if (Number.isNaN(offset)) {
        this._emitHttpError(req, res, 'tus: invalid or missing offset value');

        return;
      }

      this._emitProgress(offset, this._size);

      this._emitChunkComplete(offset - this._offset, offset, this._size);

      this._offset = offset;

      if (offset === this._size) {
        // Yay, finally done :)
        this._emitSuccess();

        this._source.close();

        return;
      }

      this._performUpload();
    }
    /**
     * Create a new HTTP request object with the given method and URL.
     *
     * @api private
     */

  }, {
    key: "_openRequest",
    value: function _openRequest(method, url) {
      var req = openRequest(method, url, this.options);
      this._req = req;
      return req;
    }
    /**
     * Remove the entry in the URL storage, if it has been saved before.
     *
     * @api private
     */

  }, {
    key: "_removeFromUrlStorage",
    value: function _removeFromUrlStorage() {
      var _this10 = this;

      if (!this._urlStorageKey) return;

      this._urlStorage.removeUpload(this._urlStorageKey)["catch"](function (err) {
        _this10._emitError(err);
      });

      this._urlStorageKey = null;
    }
    /**
     * Add the upload URL to the URL storage, if possible.
     *
     * @api private
     */

  }, {
    key: "_saveUploadInUrlStorage",
    value: function _saveUploadInUrlStorage() {
      var _this11 = this;

      // We do not store the upload URL
      // - if it was disabled in the option, or
      // - if no fingerprint was calculated for the input (i.e. a stream), or
      // - if the URL is already stored (i.e. key is set alread).
      if (!this.options.storeFingerprintForResuming || !this._fingerprint || this._urlStorageKey !== null) {
        return Promise.resolve();
      }

      var storedUpload = {
        size: this._size,
        metadata: this.options.metadata,
        creationTime: new Date().toString()
      };

      if (this._parallelUploads) {
        // Save multiple URLs if the parallelUploads option is used ...
        storedUpload.parallelUploadUrls = this._parallelUploadUrls;
      } else {
        // ... otherwise we just save the one available URL.
        storedUpload.uploadUrl = this.url;
      }

      return this._urlStorage.addUpload(this._fingerprint, storedUpload).then(function (urlStorageKey) {
        _this11._urlStorageKey = urlStorageKey;
      });
    }
    /**
     * Send a request with the provided body.
     *
     * @api private
     */

  }, {
    key: "_sendRequest",
    value: function _sendRequest(req) {
      var body = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
      return sendRequest(req, body, this.options);
    }
  }], [{
    key: "terminate",
    value: function terminate(url) {
      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
      var req = openRequest('DELETE', url, options);
      return sendRequest(req, null, options).then(function (res) {
        // A 204 response indicates a successfull request
        if (res.getStatus() === 204) {
          return;
        }

        throw new _error_js__WEBPACK_IMPORTED_MODULE_1__["default"]('tus: unexpected response while terminating upload', null, req, res);
      })["catch"](function (err) {
        if (!(err instanceof _error_js__WEBPACK_IMPORTED_MODULE_1__["default"])) {
          err = new _error_js__WEBPACK_IMPORTED_MODULE_1__["default"]('tus: failed to terminate upload', err, req, null);
        }

        if (!shouldRetry(err, 0, options)) {
          throw err;
        } // Instead of keeping track of the retry attempts, we remove the first element from the delays
        // array. If the array is empty, all retry attempts are used up and we will bubble up the error.
        // We recursively call the terminate function will removing elements from the retryDelays array.


        var delay = options.retryDelays[0];
        var remainingDelays = options.retryDelays.slice(1);

        var newOptions = _objectSpread(_objectSpread({}, options), {}, {
          retryDelays: remainingDelays
        });

        return new Promise(function (resolve) {
          return setTimeout(resolve, delay);
        }).then(function () {
          return BaseUpload.terminate(url, newOptions);
        });
      });
    }
  }]);

  return BaseUpload;
}();

function encodeMetadata(metadata) {
  return Object.entries(metadata).map(function (_ref3) {
    var _ref4 = _slicedToArray(_ref3, 2),
        key = _ref4[0],
        value = _ref4[1];

    return "".concat(key, " ").concat(js_base64__WEBPACK_IMPORTED_MODULE_4__.Base64.encode(String(value)));
  }).join(',');
}
/**
 * Checks whether a given status is in the range of the expected category.
 * For example, only a status between 200 and 299 will satisfy the category 200.
 *
 * @api private
 */


function inStatusCategory(status, category) {
  return status >= category && status < category + 100;
}
/**
 * Create a new HTTP request with the specified method and URL.
 * The necessary headers that are included in every request
 * will be added, including the request ID.
 *
 * @api private
 */


function openRequest(method, url, options) {
  var req = options.httpStack.createRequest(method, url);
  req.setHeader('Tus-Resumable', '1.0.0');
  var headers = options.headers || {};
  Object.entries(headers).forEach(function (_ref5) {
    var _ref6 = _slicedToArray(_ref5, 2),
        name = _ref6[0],
        value = _ref6[1];

    req.setHeader(name, value);
  });

  if (options.addRequestId) {
    var requestId = (0,_uuid_js__WEBPACK_IMPORTED_MODULE_3__["default"])();
    req.setHeader('X-Request-ID', requestId);
  }

  return req;
}
/**
 * Send a request with the provided body while invoking the onBeforeRequest
 * and onAfterResponse callbacks.
 *
 * @api private
 */


function sendRequest(req, body, options) {
  var onBeforeRequestPromise = typeof options.onBeforeRequest === 'function' ? Promise.resolve(options.onBeforeRequest(req)) : Promise.resolve();
  return onBeforeRequestPromise.then(function () {
    return req.send(body).then(function (res) {
      var onAfterResponsePromise = typeof options.onAfterResponse === 'function' ? Promise.resolve(options.onAfterResponse(req, res)) : Promise.resolve();
      return onAfterResponsePromise.then(function () {
        return res;
      });
    });
  });
}
/**
 * Checks whether the browser running this code has internet access.
 * This function will always return true in the node.js environment
 *
 * @api private
 */


function isOnline() {
  var online = true;

  if (typeof window !== 'undefined' && 'navigator' in window // eslint-disable-line no-undef
  && window.navigator.onLine === false) {
    // eslint-disable-line no-undef
    online = false;
  }

  return online;
}
/**
 * Checks whether or not it is ok to retry a request.
 * @param {Error} err the error returned from the last request
 * @param {number} retryAttempt the number of times the request has already been retried
 * @param {object} options tus Upload options
 *
 * @api private
 */


function shouldRetry(err, retryAttempt, options) {
  // We only attempt a retry if
  // - retryDelays option is set
  // - we didn't exceed the maxium number of retries, yet, and
  // - this error was caused by a request or it's response and
  // - the error is server error (i.e. not a status 4xx except a 409 or 423) or
  // a onShouldRetry is specified and returns true
  // - the browser does not indicate that we are offline
  if (options.retryDelays == null || retryAttempt >= options.retryDelays.length || err.originalRequest == null) {
    return false;
  }

  if (options && typeof options.onShouldRetry === 'function') {
    return options.onShouldRetry(err, retryAttempt, options);
  }

  var status = err.originalResponse ? err.originalResponse.getStatus() : 0;
  return (!inStatusCategory(status, 400) || status === 409 || status === 423) && isOnline();
}
/**
 * Resolve a relative link given the origin as source. For example,
 * if a HTTP request to http://example.com/files/ returns a Location
 * header with the value /upload/abc, the resolved URL will be:
 * http://example.com/upload/abc
 */


function resolveUrl(origin, link) {
  return new (url_parse__WEBPACK_IMPORTED_MODULE_0___default())(link, origin).toString();
}
/**
 * Calculate the start and end positions for the parts if an upload
 * is split into multiple parallel requests.
 *
 * @param {number} totalSize The byte size of the upload, which will be split.
 * @param {number} partCount The number in how many parts the upload will be split.
 * @return {object[]}
 * @api private
 */


function splitSizeIntoParts(totalSize, partCount) {
  var partSize = Math.floor(totalSize / partCount);
  var parts = [];

  for (var i = 0; i < partCount; i++) {
    parts.push({
      start: partSize * i,
      end: partSize * (i + 1)
    });
  }

  parts[partCount - 1].end = totalSize;
  return parts;
}

BaseUpload.defaultOptions = defaultOptions;
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (BaseUpload);

/***/ }),

/***/ "./node_modules/tus-js-client/lib.esm/uuid.js":
/*!****************************************************!*\
  !*** ./node_modules/tus-js-client/lib.esm/uuid.js ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ uuid)
/* harmony export */ });
/**
 * Generate a UUID v4 based on random numbers. We intentioanlly use the less
 * secure Math.random function here since the more secure crypto.getRandomNumbers
 * is not available on all platforms.
 * This is not a problem for us since we use the UUID only for generating a
 * request ID, so we can correlate server logs to client errors.
 *
 * This function is taken from following site:
 * https://stackoverflow.com/questions/105034/create-guid-uuid-in-javascript
 *
 * @return {string} The generate UUID
 */
function uuid() {
  /* eslint-disable no-bitwise */
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
    var r = Math.random() * 16 | 0;
    var v = c === 'x' ? r : r & 0x3 | 0x8;
    return v.toString(16);
  });
}

/***/ }),

/***/ "./node_modules/url-parse/index.js":
/*!*****************************************!*\
  !*** ./node_modules/url-parse/index.js ***!
  \*****************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var required = __webpack_require__(/*! requires-port */ "./node_modules/requires-port/index.js")
  , qs = __webpack_require__(/*! querystringify */ "./node_modules/querystringify/index.js")
  , controlOrWhitespace = /^[\x00-\x20\u00a0\u1680\u2000-\u200a\u2028\u2029\u202f\u205f\u3000\ufeff]+/
  , CRHTLF = /[\n\r\t]/g
  , slashes = /^[A-Za-z][A-Za-z0-9+-.]*:\/\//
  , port = /:\d+$/
  , protocolre = /^([a-z][a-z0-9.+-]*:)?(\/\/)?([\\/]+)?([\S\s]*)/i
  , windowsDriveLetter = /^[a-zA-Z]:/;

/**
 * Remove control characters and whitespace from the beginning of a string.
 *
 * @param {Object|String} str String to trim.
 * @returns {String} A new string representing `str` stripped of control
 *     characters and whitespace from its beginning.
 * @public
 */
function trimLeft(str) {
  return (str ? str : '').toString().replace(controlOrWhitespace, '');
}

/**
 * These are the parse rules for the URL parser, it informs the parser
 * about:
 *
 * 0. The char it Needs to parse, if it's a string it should be done using
 *    indexOf, RegExp using exec and NaN means set as current value.
 * 1. The property we should set when parsing this value.
 * 2. Indication if it's backwards or forward parsing, when set as number it's
 *    the value of extra chars that should be split off.
 * 3. Inherit from location if non existing in the parser.
 * 4. `toLowerCase` the resulting value.
 */
var rules = [
  ['#', 'hash'],                        // Extract from the back.
  ['?', 'query'],                       // Extract from the back.
  function sanitize(address, url) {     // Sanitize what is left of the address
    return isSpecial(url.protocol) ? address.replace(/\\/g, '/') : address;
  },
  ['/', 'pathname'],                    // Extract from the back.
  ['@', 'auth', 1],                     // Extract from the front.
  [NaN, 'host', undefined, 1, 1],       // Set left over value.
  [/:(\d*)$/, 'port', undefined, 1],    // RegExp the back.
  [NaN, 'hostname', undefined, 1, 1]    // Set left over.
];

/**
 * These properties should not be copied or inherited from. This is only needed
 * for all non blob URL's as a blob URL does not include a hash, only the
 * origin.
 *
 * @type {Object}
 * @private
 */
var ignore = { hash: 1, query: 1 };

/**
 * The location object differs when your code is loaded through a normal page,
 * Worker or through a worker using a blob. And with the blobble begins the
 * trouble as the location object will contain the URL of the blob, not the
 * location of the page where our code is loaded in. The actual origin is
 * encoded in the `pathname` so we can thankfully generate a good "default"
 * location from it so we can generate proper relative URL's again.
 *
 * @param {Object|String} loc Optional default location object.
 * @returns {Object} lolcation object.
 * @public
 */
function lolcation(loc) {
  var globalVar;

  if (typeof window !== 'undefined') globalVar = window;
  else if (typeof __webpack_require__.g !== 'undefined') globalVar = __webpack_require__.g;
  else if (typeof self !== 'undefined') globalVar = self;
  else globalVar = {};

  var location = globalVar.location || {};
  loc = loc || location;

  var finaldestination = {}
    , type = typeof loc
    , key;

  if ('blob:' === loc.protocol) {
    finaldestination = new Url(unescape(loc.pathname), {});
  } else if ('string' === type) {
    finaldestination = new Url(loc, {});
    for (key in ignore) delete finaldestination[key];
  } else if ('object' === type) {
    for (key in loc) {
      if (key in ignore) continue;
      finaldestination[key] = loc[key];
    }

    if (finaldestination.slashes === undefined) {
      finaldestination.slashes = slashes.test(loc.href);
    }
  }

  return finaldestination;
}

/**
 * Check whether a protocol scheme is special.
 *
 * @param {String} The protocol scheme of the URL
 * @return {Boolean} `true` if the protocol scheme is special, else `false`
 * @private
 */
function isSpecial(scheme) {
  return (
    scheme === 'file:' ||
    scheme === 'ftp:' ||
    scheme === 'http:' ||
    scheme === 'https:' ||
    scheme === 'ws:' ||
    scheme === 'wss:'
  );
}

/**
 * @typedef ProtocolExtract
 * @type Object
 * @property {String} protocol Protocol matched in the URL, in lowercase.
 * @property {Boolean} slashes `true` if protocol is followed by "//", else `false`.
 * @property {String} rest Rest of the URL that is not part of the protocol.
 */

/**
 * Extract protocol information from a URL with/without double slash ("//").
 *
 * @param {String} address URL we want to extract from.
 * @param {Object} location
 * @return {ProtocolExtract} Extracted information.
 * @private
 */
function extractProtocol(address, location) {
  address = trimLeft(address);
  address = address.replace(CRHTLF, '');
  location = location || {};

  var match = protocolre.exec(address);
  var protocol = match[1] ? match[1].toLowerCase() : '';
  var forwardSlashes = !!match[2];
  var otherSlashes = !!match[3];
  var slashesCount = 0;
  var rest;

  if (forwardSlashes) {
    if (otherSlashes) {
      rest = match[2] + match[3] + match[4];
      slashesCount = match[2].length + match[3].length;
    } else {
      rest = match[2] + match[4];
      slashesCount = match[2].length;
    }
  } else {
    if (otherSlashes) {
      rest = match[3] + match[4];
      slashesCount = match[3].length;
    } else {
      rest = match[4]
    }
  }

  if (protocol === 'file:') {
    if (slashesCount >= 2) {
      rest = rest.slice(2);
    }
  } else if (isSpecial(protocol)) {
    rest = match[4];
  } else if (protocol) {
    if (forwardSlashes) {
      rest = rest.slice(2);
    }
  } else if (slashesCount >= 2 && isSpecial(location.protocol)) {
    rest = match[4];
  }

  return {
    protocol: protocol,
    slashes: forwardSlashes || isSpecial(protocol),
    slashesCount: slashesCount,
    rest: rest
  };
}

/**
 * Resolve a relative URL pathname against a base URL pathname.
 *
 * @param {String} relative Pathname of the relative URL.
 * @param {String} base Pathname of the base URL.
 * @return {String} Resolved pathname.
 * @private
 */
function resolve(relative, base) {
  if (relative === '') return base;

  var path = (base || '/').split('/').slice(0, -1).concat(relative.split('/'))
    , i = path.length
    , last = path[i - 1]
    , unshift = false
    , up = 0;

  while (i--) {
    if (path[i] === '.') {
      path.splice(i, 1);
    } else if (path[i] === '..') {
      path.splice(i, 1);
      up++;
    } else if (up) {
      if (i === 0) unshift = true;
      path.splice(i, 1);
      up--;
    }
  }

  if (unshift) path.unshift('');
  if (last === '.' || last === '..') path.push('');

  return path.join('/');
}

/**
 * The actual URL instance. Instead of returning an object we've opted-in to
 * create an actual constructor as it's much more memory efficient and
 * faster and it pleases my OCD.
 *
 * It is worth noting that we should not use `URL` as class name to prevent
 * clashes with the global URL instance that got introduced in browsers.
 *
 * @constructor
 * @param {String} address URL we want to parse.
 * @param {Object|String} [location] Location defaults for relative paths.
 * @param {Boolean|Function} [parser] Parser for the query string.
 * @private
 */
function Url(address, location, parser) {
  address = trimLeft(address);
  address = address.replace(CRHTLF, '');

  if (!(this instanceof Url)) {
    return new Url(address, location, parser);
  }

  var relative, extracted, parse, instruction, index, key
    , instructions = rules.slice()
    , type = typeof location
    , url = this
    , i = 0;

  //
  // The following if statements allows this module two have compatibility with
  // 2 different API:
  //
  // 1. Node.js's `url.parse` api which accepts a URL, boolean as arguments
  //    where the boolean indicates that the query string should also be parsed.
  //
  // 2. The `URL` interface of the browser which accepts a URL, object as
  //    arguments. The supplied object will be used as default values / fall-back
  //    for relative paths.
  //
  if ('object' !== type && 'string' !== type) {
    parser = location;
    location = null;
  }

  if (parser && 'function' !== typeof parser) parser = qs.parse;

  location = lolcation(location);

  //
  // Extract protocol information before running the instructions.
  //
  extracted = extractProtocol(address || '', location);
  relative = !extracted.protocol && !extracted.slashes;
  url.slashes = extracted.slashes || relative && location.slashes;
  url.protocol = extracted.protocol || location.protocol || '';
  address = extracted.rest;

  //
  // When the authority component is absent the URL starts with a path
  // component.
  //
  if (
    extracted.protocol === 'file:' && (
      extracted.slashesCount !== 2 || windowsDriveLetter.test(address)) ||
    (!extracted.slashes &&
      (extracted.protocol ||
        extracted.slashesCount < 2 ||
        !isSpecial(url.protocol)))
  ) {
    instructions[3] = [/(.*)/, 'pathname'];
  }

  for (; i < instructions.length; i++) {
    instruction = instructions[i];

    if (typeof instruction === 'function') {
      address = instruction(address, url);
      continue;
    }

    parse = instruction[0];
    key = instruction[1];

    if (parse !== parse) {
      url[key] = address;
    } else if ('string' === typeof parse) {
      index = parse === '@'
        ? address.lastIndexOf(parse)
        : address.indexOf(parse);

      if (~index) {
        if ('number' === typeof instruction[2]) {
          url[key] = address.slice(0, index);
          address = address.slice(index + instruction[2]);
        } else {
          url[key] = address.slice(index);
          address = address.slice(0, index);
        }
      }
    } else if ((index = parse.exec(address))) {
      url[key] = index[1];
      address = address.slice(0, index.index);
    }

    url[key] = url[key] || (
      relative && instruction[3] ? location[key] || '' : ''
    );

    //
    // Hostname, host and protocol should be lowercased so they can be used to
    // create a proper `origin`.
    //
    if (instruction[4]) url[key] = url[key].toLowerCase();
  }

  //
  // Also parse the supplied query string in to an object. If we're supplied
  // with a custom parser as function use that instead of the default build-in
  // parser.
  //
  if (parser) url.query = parser(url.query);

  //
  // If the URL is relative, resolve the pathname against the base URL.
  //
  if (
      relative
    && location.slashes
    && url.pathname.charAt(0) !== '/'
    && (url.pathname !== '' || location.pathname !== '')
  ) {
    url.pathname = resolve(url.pathname, location.pathname);
  }

  //
  // Default to a / for pathname if none exists. This normalizes the URL
  // to always have a /
  //
  if (url.pathname.charAt(0) !== '/' && isSpecial(url.protocol)) {
    url.pathname = '/' + url.pathname;
  }

  //
  // We should not add port numbers if they are already the default port number
  // for a given protocol. As the host also contains the port number we're going
  // override it with the hostname which contains no port number.
  //
  if (!required(url.port, url.protocol)) {
    url.host = url.hostname;
    url.port = '';
  }

  //
  // Parse down the `auth` for the username and password.
  //
  url.username = url.password = '';

  if (url.auth) {
    index = url.auth.indexOf(':');

    if (~index) {
      url.username = url.auth.slice(0, index);
      url.username = encodeURIComponent(decodeURIComponent(url.username));

      url.password = url.auth.slice(index + 1);
      url.password = encodeURIComponent(decodeURIComponent(url.password))
    } else {
      url.username = encodeURIComponent(decodeURIComponent(url.auth));
    }

    url.auth = url.password ? url.username +':'+ url.password : url.username;
  }

  url.origin = url.protocol !== 'file:' && isSpecial(url.protocol) && url.host
    ? url.protocol +'//'+ url.host
    : 'null';

  //
  // The href is just the compiled result.
  //
  url.href = url.toString();
}

/**
 * This is convenience method for changing properties in the URL instance to
 * insure that they all propagate correctly.
 *
 * @param {String} part          Property we need to adjust.
 * @param {Mixed} value          The newly assigned value.
 * @param {Boolean|Function} fn  When setting the query, it will be the function
 *                               used to parse the query.
 *                               When setting the protocol, double slash will be
 *                               removed from the final url if it is true.
 * @returns {URL} URL instance for chaining.
 * @public
 */
function set(part, value, fn) {
  var url = this;

  switch (part) {
    case 'query':
      if ('string' === typeof value && value.length) {
        value = (fn || qs.parse)(value);
      }

      url[part] = value;
      break;

    case 'port':
      url[part] = value;

      if (!required(value, url.protocol)) {
        url.host = url.hostname;
        url[part] = '';
      } else if (value) {
        url.host = url.hostname +':'+ value;
      }

      break;

    case 'hostname':
      url[part] = value;

      if (url.port) value += ':'+ url.port;
      url.host = value;
      break;

    case 'host':
      url[part] = value;

      if (port.test(value)) {
        value = value.split(':');
        url.port = value.pop();
        url.hostname = value.join(':');
      } else {
        url.hostname = value;
        url.port = '';
      }

      break;

    case 'protocol':
      url.protocol = value.toLowerCase();
      url.slashes = !fn;
      break;

    case 'pathname':
    case 'hash':
      if (value) {
        var char = part === 'pathname' ? '/' : '#';
        url[part] = value.charAt(0) !== char ? char + value : value;
      } else {
        url[part] = value;
      }
      break;

    case 'username':
    case 'password':
      url[part] = encodeURIComponent(value);
      break;

    case 'auth':
      var index = value.indexOf(':');

      if (~index) {
        url.username = value.slice(0, index);
        url.username = encodeURIComponent(decodeURIComponent(url.username));

        url.password = value.slice(index + 1);
        url.password = encodeURIComponent(decodeURIComponent(url.password));
      } else {
        url.username = encodeURIComponent(decodeURIComponent(value));
      }
  }

  for (var i = 0; i < rules.length; i++) {
    var ins = rules[i];

    if (ins[4]) url[ins[1]] = url[ins[1]].toLowerCase();
  }

  url.auth = url.password ? url.username +':'+ url.password : url.username;

  url.origin = url.protocol !== 'file:' && isSpecial(url.protocol) && url.host
    ? url.protocol +'//'+ url.host
    : 'null';

  url.href = url.toString();

  return url;
}

/**
 * Transform the properties back in to a valid and full URL string.
 *
 * @param {Function} stringify Optional query stringify function.
 * @returns {String} Compiled version of the URL.
 * @public
 */
function toString(stringify) {
  if (!stringify || 'function' !== typeof stringify) stringify = qs.stringify;

  var query
    , url = this
    , host = url.host
    , protocol = url.protocol;

  if (protocol && protocol.charAt(protocol.length - 1) !== ':') protocol += ':';

  var result =
    protocol +
    ((url.protocol && url.slashes) || isSpecial(url.protocol) ? '//' : '');

  if (url.username) {
    result += url.username;
    if (url.password) result += ':'+ url.password;
    result += '@';
  } else if (url.password) {
    result += ':'+ url.password;
    result += '@';
  } else if (
    url.protocol !== 'file:' &&
    isSpecial(url.protocol) &&
    !host &&
    url.pathname !== '/'
  ) {
    //
    // Add back the empty userinfo, otherwise the original invalid URL
    // might be transformed into a valid one with `url.pathname` as host.
    //
    result += '@';
  }

  //
  // Trailing colon is removed from `url.host` when it is parsed. If it still
  // ends with a colon, then add back the trailing colon that was removed. This
  // prevents an invalid URL from being transformed into a valid one.
  //
  if (host[host.length - 1] === ':' || (port.test(url.hostname) && !url.port)) {
    host += ':';
  }

  result += host + url.pathname;

  query = 'object' === typeof url.query ? stringify(url.query) : url.query;
  if (query) result += '?' !== query.charAt(0) ? '?'+ query : query;

  if (url.hash) result += url.hash;

  return result;
}

Url.prototype = { set: set, toString: toString };

//
// Expose the URL parser and some additional properties that might be useful for
// others or testing.
//
Url.extractProtocol = extractProtocol;
Url.location = lolcation;
Url.trimLeft = trimLeft;
Url.qs = qs;

module.exports = Url;


/***/ }),

/***/ "./node_modules/wildcard/index.js":
/*!****************************************!*\
  !*** ./node_modules/wildcard/index.js ***!
  \****************************************/
/***/ ((module) => {

"use strict";
/* jshint node: true */


/**
  # wildcard

  Very simple wildcard matching, which is designed to provide the same
  functionality that is found in the
  [eve](https://github.com/adobe-webplatform/eve) eventing library.

  ## Usage

  It works with strings:

  <<< examples/strings.js

  Arrays:

  <<< examples/arrays.js

  Objects (matching against keys):

  <<< examples/objects.js

  While the library works in Node, if you are are looking for file-based
  wildcard matching then you should have a look at:

  <https://github.com/isaacs/node-glob>
**/

function WildcardMatcher(text, separator) {
  this.text = text = text || '';
  this.hasWild = ~text.indexOf('*');
  this.separator = separator;
  this.parts = text.split(separator);
}

WildcardMatcher.prototype.match = function(input) {
  var matches = true;
  var parts = this.parts;
  var ii;
  var partsCount = parts.length;
  var testParts;

  if (typeof input == 'string' || input instanceof String) {
    if (!this.hasWild && this.text != input) {
      matches = false;
    } else {
      testParts = (input || '').split(this.separator);
      for (ii = 0; matches && ii < partsCount; ii++) {
        if (parts[ii] === '*')  {
          continue;
        } else if (ii < testParts.length) {
          matches = parts[ii] === testParts[ii];
        } else {
          matches = false;
        }
      }

      // If matches, then return the component parts
      matches = matches && testParts;
    }
  }
  else if (typeof input.splice == 'function') {
    matches = [];

    for (ii = input.length; ii--; ) {
      if (this.match(input[ii])) {
        matches[matches.length] = input[ii];
      }
    }
  }
  else if (typeof input == 'object') {
    matches = {};

    for (var key in input) {
      if (this.match(key)) {
        matches[key] = input[key];
      }
    }
  }

  return matches;
};

module.exports = function(text, test, separator) {
  var matcher = new WildcardMatcher(text, separator || /[\/\.]/);
  if (typeof test != 'undefined') {
    return matcher.match(test);
  }

  return matcher;
};


/***/ }),

/***/ "./src/jgDashboard/lib/Dashboard.js":
/*!******************************************!*\
  !*** ./src/jgDashboard/lib/Dashboard.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ jgDashboard)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var memoize_one__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! memoize-one */ "./src/jgDashboard/node_modules/memoize-one/dist/memoize-one.esm.js");
/* harmony import */ var _uppy_dashboard_lib_Dashboard_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @uppy/dashboard/lib/Dashboard.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/Dashboard.js");
/* harmony import */ var _components_Dashboard_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./components/Dashboard.js */ "./src/jgDashboard/lib/components/Dashboard.js");
function _classPrivateFieldLooseBase(receiver, privateKey) { if (!Object.prototype.hasOwnProperty.call(receiver, privateKey)) { throw new TypeError("attempted to use private field on non-instance"); } return receiver; }

var id = 0;

function _classPrivateFieldLooseKey(name) { return "__private_" + id++ + "_" + name; }






const packageJson = {
  "version": "3.4.0"
};

const memoize = memoize_one__WEBPACK_IMPORTED_MODULE_3__["default"]["default"] || memoize_one__WEBPACK_IMPORTED_MODULE_3__["default"];

const TAB_KEY = 9;
const ESC_KEY = 27;

function createPromise() {
  const o = {};
  o.promise = new Promise((resolve, reject) => {
    o.resolve = resolve;
    o.reject = reject;
  });
  return o;
}

function defaultPickerIcon() {
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    "aria-hidden": "true",
    focusable: "false",
    width: "30",
    height: "30",
    viewBox: "0 0 30 30"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    d: "M15 30c8.284 0 15-6.716 15-15 0-8.284-6.716-15-15-15C6.716 0 0 6.716 0 15c0 8.284 6.716 15 15 15zm4.258-12.676v6.846h-8.426v-6.846H5.204l9.82-12.364 9.82 12.364H19.26z"
  }));
}
/**
 * Dashboard UI with previews, metadata editing, tabs for various services and more
 */


var _disabledNodes = /*#__PURE__*/_classPrivateFieldLooseKey("disabledNodes");

var _generateLargeThumbnailIfSingleFile = /*#__PURE__*/_classPrivateFieldLooseKey("generateLargeThumbnailIfSingleFile");

var _openFileEditorWhenFilesAdded = /*#__PURE__*/_classPrivateFieldLooseKey("openFileEditorWhenFilesAdded");

var _attachRenderFunctionToTarget = /*#__PURE__*/_classPrivateFieldLooseKey("attachRenderFunctionToTarget");

var _isTargetSupported = /*#__PURE__*/_classPrivateFieldLooseKey("isTargetSupported");

var _getAcquirers = /*#__PURE__*/_classPrivateFieldLooseKey("getAcquirers");

var _getProgressIndicators = /*#__PURE__*/_classPrivateFieldLooseKey("getProgressIndicators");

var _getEditors = /*#__PURE__*/_classPrivateFieldLooseKey("getEditors");

class jgDashboard extends _uppy_dashboard_lib_Dashboard_js__WEBPACK_IMPORTED_MODULE_1__["default"] {
  constructor(uppy, _opts) {
    var _this;

    super(uppy, _opts);
    _this = this;
    Object.defineProperty(this, _disabledNodes, {
      writable: true,
      value: null
    });

    // Object.defineProperty(this, _attachRenderFunctionToTarget, {
    //   writable: true,
    //   value: target => {
    //     const plugin = this.uppy.getPlugin(target.id);
    //     return { ...target,
    //       icon: plugin.icon || this.opts.defaultPickerIcon,
    //       render: plugin.render
    //     };
    //   }
    // });
    // Object.defineProperty(this, _isTargetSupported, {
    //   writable: true,
    //   value: target => {
    //     const plugin = this.uppy.getPlugin(target.id); // If the plugin does not provide a `supported` check, assume the plugin works everywhere.

    //     if (typeof plugin.isSupported !== 'function') {
    //       return true;
    //     }

    //     return plugin.isSupported();
    //   }
    // });
    // Object.defineProperty(this, _getAcquirers, {
    //   writable: true,
    //   value: memoize(targets => {
    //     return targets.filter(target => target.type === 'acquirer' && _classPrivateFieldLooseBase(this, _isTargetSupported)[_isTargetSupported](target)).map(_classPrivateFieldLooseBase(this, _attachRenderFunctionToTarget)[_attachRenderFunctionToTarget]);
    //   })
    // });
    // Object.defineProperty(this, _getProgressIndicators, {
    //   writable: true,
    //   value: memoize(targets => {
    //     return targets.filter(target => target.type === 'progressindicator').map(_classPrivateFieldLooseBase(this, _attachRenderFunctionToTarget)[_attachRenderFunctionToTarget]);
    //   })
    // });
    // Object.defineProperty(this, _getEditors, {
    //   writable: true,
    //   value: memoize(targets => {
    //     return targets.filter(target => target.type === 'editor').map(_classPrivateFieldLooseBase(this, _attachRenderFunctionToTarget)[_attachRenderFunctionToTarget]);
    //   })
    // });

    this.render = state => {
      const pluginState = this.getPluginState();
      const {
        files,
        capabilities,
        allowNewUpload
      } = state;
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
        isAllPaused
      } = this.uppy.getObjectOfFilesPerState();

      const acquirers = _classPrivateFieldLooseBase(this, _getAcquirers)[_getAcquirers](pluginState.targets);

      const progressindicators = _classPrivateFieldLooseBase(this, _getProgressIndicators)[_getProgressIndicators](pluginState.targets);

      const editors = _classPrivateFieldLooseBase(this, _getEditors)[_getEditors](pluginState.targets);

      let theme;

      if (this.opts.theme === 'auto') {
        theme = capabilities.darkMode ? 'dark' : 'light';
      } else {
        theme = this.opts.theme;
      }

      if (['files', 'folders', 'both'].indexOf(this.opts.fileManagerSelectionType) < 0) {
        this.opts.fileManagerSelectionType = 'files'; // eslint-disable-next-line no-console

        console.warn(`Unsupported option for "fileManagerSelectionType". Using default of "${this.opts.fileManagerSelectionType}".`);
      }

      return (0,_components_Dashboard_js__WEBPACK_IMPORTED_MODULE_2__["default"])({
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
        handleDrop: this.handleDrop
      });
    };
  }

}
_uppy_dashboard_lib_Dashboard_js__WEBPACK_IMPORTED_MODULE_1__["default"].VERSION = packageJson.version;

/***/ }),

/***/ "./src/jgDashboard/lib/components/Dashboard.js":
/*!*****************************************************!*\
  !*** ./src/jgDashboard/lib/components/Dashboard.js ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Dashboard)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! classnames */ "./src/jgDashboard/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _uppy_utils_lib_isDragDropSupported__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @uppy/utils/lib/isDragDropSupported */ "./src/jgDashboard/node_modules/@uppy/utils/lib/isDragDropSupported.js");
/* harmony import */ var _FileList_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./FileList.js */ "./src/jgDashboard/lib/components/FileList.js");
/* harmony import */ var _uppy_dashboard_lib_components_AddFiles_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @uppy/dashboard/lib/components/AddFiles.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/AddFiles.js");
/* harmony import */ var _uppy_dashboard_lib_components_AddFilesPanel_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @uppy/dashboard/lib/components/AddFilesPanel.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/AddFilesPanel.js");
/* harmony import */ var _uppy_dashboard_lib_components_PickerPanelContent_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @uppy/dashboard/lib/components/PickerPanelContent.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/PickerPanelContent.js");
/* harmony import */ var _uppy_dashboard_lib_components_EditorPanel_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @uppy/dashboard/lib/components/EditorPanel.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/EditorPanel.js");
/* harmony import */ var _uppy_dashboard_lib_components_PickerPanelTopBar_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @uppy/dashboard/lib/components/PickerPanelTopBar.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/PickerPanelTopBar.js");
/* harmony import */ var _uppy_dashboard_lib_components_FileCard_index_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @uppy/dashboard/lib/components/FileCard/index.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileCard/index.js");
/* harmony import */ var _uppy_dashboard_lib_components_Slide_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @uppy/dashboard/lib/components/Slide.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/Slide.js");
function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }













// http://dev.edenspiekermann.com/2016/02/11/introducing-accessible-modal-dialog
// https://github.com/ghosh/micromodal

const WIDTH_XL = 900;
const WIDTH_LG = 700;
const WIDTH_MD = 576;
const HEIGHT_MD = 330; // We might want to enable this in the future
// const HEIGHT_LG = 400
// const HEIGHT_XL = 460

function Dashboard(props) {
  const isNoFiles = props.totalFileCount === 0;
  const isSingleFile = props.totalFileCount === 1;
  const isSizeMD = props.containerWidth > WIDTH_MD;
  const isSizeHeightMD = props.containerHeight > HEIGHT_MD;
  const dashboardClassName = classnames__WEBPACK_IMPORTED_MODULE_1___default()({
    'uppy-Dashboard': true,
    'uppy-Dashboard--isDisabled': props.disabled,
    'uppy-Dashboard--animateOpenClose': props.animateOpenClose,
    'uppy-Dashboard--isClosing': props.isClosing,
    'uppy-Dashboard--isDraggingOver': props.isDraggingOver,
    'uppy-Dashboard--modal': !props.inline,
    'uppy-size--md': props.containerWidth > WIDTH_MD,
    'uppy-size--lg': props.containerWidth > WIDTH_LG,
    'uppy-size--xl': props.containerWidth > WIDTH_XL,
    'uppy-size--height-md': props.containerHeight > HEIGHT_MD,
    // We might want to enable this in the future
    // 'uppy-size--height-lg': props.containerHeight > HEIGHT_LG,
    // 'uppy-size--height-xl': props.containerHeight > HEIGHT_XL,
    'uppy-Dashboard--isAddFilesPanelVisible': props.showAddFilesPanel,
    'uppy-Dashboard--isInnerWrapVisible': props.areInsidesReadyToBeVisible,
    // Only enable “centered single file” mode when Dashboard is tall enough
    'uppy-Dashboard--singleFile': props.singleFileFullScreen && isSingleFile && isSizeHeightMD
  }); // Important: keep these in sync with the percent width values in `src/components/FileItem/index.scss`.

  let itemsPerRow = 1; // mobile

  if (props.containerWidth > WIDTH_XL) {
    itemsPerRow = 5;
  } else if (props.containerWidth > WIDTH_LG) {
    itemsPerRow = 4;
  } else if (props.containerWidth > WIDTH_MD) {
    itemsPerRow = 3;
  }

  const showFileList = props.showSelectedFiles && !isNoFiles;
  const numberOfFilesForRecovery = props.recoveredState ? Object.keys(props.recoveredState.files).length : null;
  const numberOfGhosts = props.files ? Object.keys(props.files).filter(fileID => props.files[fileID].isGhost).length : null;

  const renderRestoredText = () => {
    if (numberOfGhosts > 0) {
      return props.i18n('recoveredXFiles', {
        smart_count: numberOfGhosts
      });
    }

    return props.i18n('recoveredAllFiles');
  };

  const dashboard = (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: dashboardClassName,
    "data-uppy-theme": props.theme,
    "data-uppy-num-acquirers": props.acquirers.length,
    "data-uppy-drag-drop-supported": !props.disableLocalFiles && (0,_uppy_utils_lib_isDragDropSupported__WEBPACK_IMPORTED_MODULE_2__["default"])(),
    "aria-hidden": props.inline ? 'false' : props.isHidden,
    "aria-disabled": props.disabled,
    "aria-label": !props.inline ? props.i18n('dashboardWindowTitle') : props.i18n('dashboardTitle'),
    onPaste: props.handlePaste,
    onDragOver: props.handleDragOver,
    onDragLeave: props.handleDragLeave,
    onDrop: props.handleDrop
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    "aria-hidden": "true",
    className: "uppy-Dashboard-overlay",
    tabIndex: -1,
    onClick: props.handleClickOutside
  }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-inner",
    "aria-modal": !props.inline && 'true',
    role: !props.inline && 'dialog',
    style: {
      width: props.inline && props.width ? props.width : '',
      height: props.inline && props.height ? props.height : ''
    }
  }, !props.inline ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    className: "uppy-u-reset uppy-Dashboard-close",
    type: "button",
    "aria-label": props.i18n('closeModal'),
    title: props.i18n('closeModal'),
    onClick: props.closeModal
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("span", {
    "aria-hidden": "true"
  }, "\xD7")) : null, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-innerWrap"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-dropFilesHereHint"
  }, props.i18n('dropHint')), showFileList && (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_uppy_dashboard_lib_components_PickerPanelTopBar_js__WEBPACK_IMPORTED_MODULE_8__["default"], props), numberOfFilesForRecovery && (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-serviceMsg"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    className: "uppy-Dashboard-serviceMsg-icon",
    "aria-hidden": "true",
    focusable: "false",
    width: "21",
    height: "16",
    viewBox: "0 0 24 19"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("g", {
    transform: "translate(0 -1)",
    fill: "none",
    fillRule: "evenodd"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    d: "M12.857 1.43l10.234 17.056A1 1 0 0122.234 20H1.766a1 1 0 01-.857-1.514L11.143 1.429a1 1 0 011.714 0z",
    fill: "#FFD300"
  }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    fill: "#000",
    d: "M11 6h2l-.3 8h-1.4z"
  }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("circle", {
    fill: "#000",
    cx: "12",
    cy: "17",
    r: "1"
  }))), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("strong", {
    className: "uppy-Dashboard-serviceMsg-title"
  }, props.i18n('sessionRestored')), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-serviceMsg-text"
  }, renderRestoredText())), showFileList ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_FileList_js__WEBPACK_IMPORTED_MODULE_3__["default"], {
    id: props.id,
    error: props.error,
    i18n: props.i18n,
    uppy: props.uppy,
    files: props.files,
    acquirers: props.acquirers,
    resumableUploads: props.resumableUploads,
    hideRetryButton: props.hideRetryButton,
    hidePauseResumeButton: props.hidePauseResumeButton,
    hideCancelButton: props.hideCancelButton,
    showLinkToFileUploadResult: props.showLinkToFileUploadResult,
    showRemoveButtonAfterComplete: props.showRemoveButtonAfterComplete,
    isWide: props.isWide,
    metaFields: props.metaFields,
    toggleFileCard: props.toggleFileCard,
    handleRequestThumbnail: props.handleRequestThumbnail,
    handleCancelThumbnail: props.handleCancelThumbnail,
    recoveredState: props.recoveredState,
    individualCancellation: props.individualCancellation,
    openFileEditor: props.openFileEditor,
    canEditFile: props.canEditFile,
    toggleAddFilesPanel: props.toggleAddFilesPanel,
    isSingleFile: isSingleFile,
    itemsPerRow: itemsPerRow
  }) : // eslint-disable-next-line react/jsx-props-no-spreading
  (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_uppy_dashboard_lib_components_AddFiles_js__WEBPACK_IMPORTED_MODULE_4__["default"], _extends({}, props, {
    isSizeMD: isSizeMD
  })), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_uppy_dashboard_lib_components_Slide_js__WEBPACK_IMPORTED_MODULE_10__["default"], null, props.showAddFilesPanel ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_uppy_dashboard_lib_components_AddFilesPanel_js__WEBPACK_IMPORTED_MODULE_5__["default"], _extends({
    key: "AddFiles"
  }, props, {
    isSizeMD: isSizeMD
  })) : null), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_uppy_dashboard_lib_components_Slide_js__WEBPACK_IMPORTED_MODULE_10__["default"], null, props.fileCardFor ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_uppy_dashboard_lib_components_FileCard_index_js__WEBPACK_IMPORTED_MODULE_9__["default"], _extends({
    key: "FileCard"
  }, props)) : null), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_uppy_dashboard_lib_components_Slide_js__WEBPACK_IMPORTED_MODULE_10__["default"], null, props.activePickerPanel ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_uppy_dashboard_lib_components_PickerPanelContent_js__WEBPACK_IMPORTED_MODULE_6__["default"], _extends({
    key: "Picker"
  }, props)) : null), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_uppy_dashboard_lib_components_Slide_js__WEBPACK_IMPORTED_MODULE_10__["default"], null, props.showFileEditor ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_uppy_dashboard_lib_components_EditorPanel_js__WEBPACK_IMPORTED_MODULE_7__["default"], _extends({
    key: "Editor"
  }, props)) : null), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-progressindicators"
  }, props.progressindicators.map(target => {
    return props.uppy.getPlugin(target.id).render(props.state);
  })))));
  return dashboard;
}

/***/ }),

/***/ "./src/jgDashboard/lib/components/FileItem/FileInfo/index.js":
/*!*******************************************************************!*\
  !*** ./src/jgDashboard/lib/components/FileItem/FileInfo/index.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ FileInfo)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var _transloadit_prettier_bytes__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @transloadit/prettier-bytes */ "./src/jgDashboard/node_modules/@transloadit/prettier-bytes/prettierBytes.js");
/* harmony import */ var _transloadit_prettier_bytes__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_transloadit_prettier_bytes__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _uppy_utils_lib_truncateString__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @uppy/utils/lib/truncateString */ "./src/jgDashboard/node_modules/@uppy/utils/lib/truncateString.js");
/* harmony import */ var _uppy_dashboard_lib_components_FileItem_MetaErrorMessage_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @uppy/dashboard/lib/components/FileItem/MetaErrorMessage.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/MetaErrorMessage.js");





const renderFileName = props => {
  const {
    author,
    name
  } = props.file.meta;

  function getMaxNameLength() {
    if (props.isSingleFile && props.containerHeight >= 350) {
      return 90;
    }

    if (props.containerWidth <= 352) {
      return 35;
    }

    if (props.containerWidth <= 576) {
      return 60;
    } // When `author` is present, we want to make sure
    // the file name fits on one line so we can place
    // the author on the second line.


    return author ? 20 : 30;
  }

  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-Item-name",
    title: name
  }, (0,_uppy_utils_lib_truncateString__WEBPACK_IMPORTED_MODULE_2__["default"])(name, getMaxNameLength()));
};

const renderAuthor = props => {
  const {
    author
  } = props.file.meta;
  const {
    providerName
  } = props.file.remote;
  const dot = `\u00B7`;

  if (!author) {
    return null;
  }

  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-Item-author"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("a", {
    href: `${author.url}?utm_source=Companion&utm_medium=referral`,
    target: "_blank",
    rel: "noopener noreferrer"
  }, (0,_uppy_utils_lib_truncateString__WEBPACK_IMPORTED_MODULE_2__["default"])(author.name, 13)), providerName ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(preact__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, ` ${dot} `, providerName, ` ${dot} `) : null);
};

const renderFileSize = props => props.file.size && (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
  className: "uppy-Dashboard-Item-statusSize"
}, _transloadit_prettier_bytes__WEBPACK_IMPORTED_MODULE_1___default()(props.file.size));

const renderDebugBtn = props => props.file.debugBtn && (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
  className: "uppy-Dashboard-Item-debug"
}, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    class: "btn btn-"+props.file.debugBtn.type+" btn-sm "+props.file.debugBtn.style,
    type: "button",
    'data-bs-toggle': "modal",
    'data-bs-target': "#modal"+props.file.debugBtn.uuid,
  }, props.file.debugBtn.txt));

const renderState = props => props.file.statetxt && (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
  className: "uppy-Dashboard-Item-state"
}, props.file.statetxt);

const ReSelectButton = props => props.file.isGhost && (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("span", null, ' \u2022 ', (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
  className: "uppy-u-reset uppy-c-btn uppy-Dashboard-Item-reSelect",
  type: "button",
  onClick: props.toggleAddFilesPanel
}, props.i18n('reSelect')));

const ErrorButton = _ref => {
  let {
    file,
    onClick
  } = _ref;

  if (file.error) {
    return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
      className: "uppy-u-reset uppy-c-btn uppy-Dashboard-Item-errorDetails",
      "aria-label": file.error,
      "data-microtip-position": "bottom",
      "data-microtip-size": "medium",
      onClick: onClick,
      type: "button"
    }, "?");
  }

  return null;
};

function FileInfo(props) {
  const { file } = props;
  
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-Item-fileInfo",
    "data-uppy-file-source": file.source
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-Item-fileName"
  }, renderFileName(props), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(ErrorButton, {
    file: props.file // eslint-disable-next-line no-alert
    ,
    onClick: () => alert(props.file.error) // TODO: move to a custom alert implementation

  })), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-Item-status"
  }, renderAuthor(props), renderFileSize(props), renderState(props), renderDebugBtn(props), ReSelectButton(props)), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_uppy_dashboard_lib_components_FileItem_MetaErrorMessage_js__WEBPACK_IMPORTED_MODULE_3__["default"], {
    file: props.file,
    i18n: props.i18n,
    toggleFileCard: props.toggleFileCard,
    metaFields: props.metaFields
  }));
}

/***/ }),

/***/ "./src/jgDashboard/lib/components/FileItem/index.js":
/*!**********************************************************!*\
  !*** ./src/jgDashboard/lib/components/FileItem/index.js ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ FileItem)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! classnames */ "./src/jgDashboard/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _uppy_dashboard_lib_components_FileItem_index_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @uppy/dashboard/lib/components/FileItem/index.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/index.js");
/* harmony import */ var _uppy_dashboard_lib_components_FileItem_FilePreviewAndLink_index_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @uppy/dashboard/lib/components/FileItem/FilePreviewAndLink/index.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/FilePreviewAndLink/index.js");
/* harmony import */ var _uppy_dashboard_lib_components_FileItem_FileProgress_index_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @uppy/dashboard/lib/components/FileItem/FileProgress/index.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/FileProgress/index.js");
/* harmony import */ var _FileInfo_index_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./FileInfo/index.js */ "./src/jgDashboard/lib/components/FileItem/FileInfo/index.js");
/* harmony import */ var _uppy_dashboard_lib_components_FileItem_Buttons_index_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @uppy/dashboard/lib/components/FileItem/Buttons/index.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/Buttons/index.js");









class FileItem extends _uppy_dashboard_lib_components_FileItem_index_js__WEBPACK_IMPORTED_MODULE_2__["default"] {
  render() {
    const { file } = this.props;

    const isProcessing = file.progress.preprocess || file.progress.postprocess;
    const isUploaded = file.progress.uploadComplete && !isProcessing && !file.error;
    const uploadInProgressOrComplete = file.progress.uploadStarted || isProcessing;
    const uploadInProgress = file.progress.uploadStarted && !file.progress.uploadComplete || isProcessing;
    const error = file.error || false;
    
    // File that Golden Retriever was able to partly restore (only meta, not blob),
    // users still need to re-add it, so it’s a ghost

    const { isGhost } = file;

    let showRemoveButton = this.props.individualCancellation ? !isUploaded : !uploadInProgress && !isUploaded;

    if (isUploaded && this.props.showRemoveButtonAfterComplete) {
      showRemoveButton = true;
    }

    const dashboardItemClass = classnames__WEBPACK_IMPORTED_MODULE_1___default()({
      'uppy-Dashboard-Item': true,
      'is-inprogress': uploadInProgress && !this.props.recoveredState,
      'is-processing': isProcessing,
      'is-complete': isUploaded,
      'is-error': !!error,
      'is-resumable': this.props.resumableUploads,
      'is-noIndividualCancellation': !this.props.individualCancellation,
      'is-ghost': isGhost
    });

    return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
      className: dashboardItemClass,
      id: `uppy_${file.id}`,
      role: this.props.role
    }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
      className: "uppy-Dashboard-Item-preview"
    }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_uppy_dashboard_lib_components_FileItem_FilePreviewAndLink_index_js__WEBPACK_IMPORTED_MODULE_3__["default"], {
      file: file,
      showLinkToFileUploadResult: this.props.showLinkToFileUploadResult,
      i18n: this.props.i18n,
      toggleFileCard: this.props.toggleFileCard,
      metaFields: this.props.metaFields
    }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_uppy_dashboard_lib_components_FileItem_FileProgress_index_js__WEBPACK_IMPORTED_MODULE_4__["default"], {
      uppy: this.props.uppy,
      file: file,
      error: error,
      isUploaded: isUploaded,
      hideRetryButton: this.props.hideRetryButton,
      hideCancelButton: this.props.hideCancelButton,
      hidePauseResumeButton: this.props.hidePauseResumeButton,
      recoveredState: this.props.recoveredState,
      showRemoveButtonAfterComplete: this.props.showRemoveButtonAfterComplete,
      resumableUploads: this.props.resumableUploads,
      individualCancellation: this.props.individualCancellation,
      i18n: this.props.i18n
    })), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
      className: "uppy-Dashboard-Item-fileInfoAndButtons"
    }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_FileInfo_index_js__WEBPACK_IMPORTED_MODULE_5__["default"], {
      file: file,
      id: this.props.id,
      acquirers: this.props.acquirers,
      containerWidth: this.props.containerWidth,
      containerHeight: this.props.containerHeight,
      i18n: this.props.i18n,
      toggleAddFilesPanel: this.props.toggleAddFilesPanel,
      toggleFileCard: this.props.toggleFileCard,
      metaFields: this.props.metaFields,
      isSingleFile: this.props.isSingleFile
    }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_uppy_dashboard_lib_components_FileItem_Buttons_index_js__WEBPACK_IMPORTED_MODULE_6__["default"], {
      file: file,
      metaFields: this.props.metaFields,
      showLinkToFileUploadResult: this.props.showLinkToFileUploadResult,
      showRemoveButton: showRemoveButton,
      canEditFile: this.props.canEditFile,
      uploadInProgressOrComplete: uploadInProgressOrComplete,
      toggleFileCard: this.props.toggleFileCard,
      openFileEditor: this.props.openFileEditor,
      uppy: this.props.uppy,
      i18n: this.props.i18n
    })));
  }
}

/***/ }),

/***/ "./src/jgDashboard/lib/components/FileList.js":
/*!****************************************************!*\
  !*** ./src/jgDashboard/lib/components/FileList.js ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var preact_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! preact/hooks */ "./src/jgDashboard/node_modules/preact/hooks/dist/hooks.module.js");
/* harmony import */ var _FileItem_index_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FileItem/index.js */ "./src/jgDashboard/lib/components/FileItem/index.js");
/* harmony import */ var _uppy_dashboard_lib_components_VirtualList_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @uppy/dashboard/lib/components/VirtualList.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/VirtualList.js");





function chunks(list, size) {
  const chunked = [];
  let currentChunk = [];
  list.forEach(item => {
    if (currentChunk.length < size) {
      currentChunk.push(item);
    } else {
      chunked.push(currentChunk);
      currentChunk = [item];
    }
  });
  if (currentChunk.length) chunked.push(currentChunk);
  return chunked;
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_ref => {
  let {
    id,
    error,
    i18n,
    uppy,
    files,
    acquirers,
    resumableUploads,
    hideRetryButton,
    hidePauseResumeButton,
    hideCancelButton,
    showLinkToFileUploadResult,
    showRemoveButtonAfterComplete,
    isWide,
    metaFields,
    isSingleFile,
    toggleFileCard,
    handleRequestThumbnail,
    handleCancelThumbnail,
    recoveredState,
    individualCancellation,
    itemsPerRow,
    openFileEditor,
    canEditFile,
    toggleAddFilesPanel,
    containerWidth,
    containerHeight
  } = _ref;
  // It's not great that this is hardcoded!
  // It's ESPECIALLY not great that this is checking against `itemsPerRow`!
  const rowHeight = itemsPerRow === 1 // Mobile
  ? 71 // 190px height + 2 * 5px margin
  : 200; // Sort files by file.isGhost, ghost files first, only if recoveredState is present

  const rows = (0,preact_hooks__WEBPACK_IMPORTED_MODULE_1__.useMemo)(() => {
    const sortByGhostComesFirst = (file1, file2) => files[file2].isGhost - files[file1].isGhost;

    const fileIds = Object.keys(files);
    if (recoveredState) fileIds.sort(sortByGhostComesFirst);
    return chunks(fileIds, itemsPerRow);
  }, [files, itemsPerRow, recoveredState]);

  const renderRow = row => // The `role="presentation` attribute ensures that the list items are properly
  // associated with the `VirtualList` element.
  // We use the first file ID as the key—this should not change across scroll rerenders
  (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    class: "uppy-Dashboard-filesInner",
    role: "presentation",
    key: row[0]
  }, row.map(fileID => (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_FileItem_index_js__WEBPACK_IMPORTED_MODULE_2__["default"], {
    key: fileID,
    uppy: uppy // FIXME This is confusing, it's actually the Dashboard's plugin ID
    ,
    id: id,
    error: error // TODO move this to context
    ,
    i18n: i18n // features
    ,
    acquirers: acquirers,
    resumableUploads: resumableUploads,
    individualCancellation: individualCancellation // visual options
    ,
    hideRetryButton: hideRetryButton,
    hidePauseResumeButton: hidePauseResumeButton,
    hideCancelButton: hideCancelButton,
    showLinkToFileUploadResult: showLinkToFileUploadResult,
    showRemoveButtonAfterComplete: showRemoveButtonAfterComplete,
    isWide: isWide,
    metaFields: metaFields,
    recoveredState: recoveredState,
    isSingleFile: isSingleFile,
    containerWidth: containerWidth,
    containerHeight: containerHeight // callbacks
    ,
    toggleFileCard: toggleFileCard,
    handleRequestThumbnail: handleRequestThumbnail,
    handleCancelThumbnail: handleCancelThumbnail,
    role: "listitem",
    openFileEditor: openFileEditor,
    canEditFile: canEditFile,
    toggleAddFilesPanel: toggleAddFilesPanel,
    file: files[fileID]
  })));

  if (isSingleFile) {
    return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
      class: "uppy-Dashboard-files"
    }, renderRow(rows[0]));
  }

  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_uppy_dashboard_lib_components_VirtualList_js__WEBPACK_IMPORTED_MODULE_3__["default"], {
    class: "uppy-Dashboard-files",
    role: "list",
    data: rows,
    renderRow: renderRow,
    rowHeight: rowHeight
  });
});

/***/ }),

/***/ "./src/jgDashboard/lib/index.js":
/*!**************************************!*\
  !*** ./src/jgDashboard/lib/index.js ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _Dashboard_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _Dashboard_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Dashboard.js */ "./src/jgDashboard/lib/Dashboard.js");


/***/ }),

/***/ "./src/jgDashboard/node_modules/@transloadit/prettier-bytes/prettierBytes.js":
/*!***********************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@transloadit/prettier-bytes/prettierBytes.js ***!
  \***********************************************************************************/
/***/ ((module) => {

// Adapted from https://github.com/Flet/prettier-bytes/
// Changing 1000 bytes to 1024, so we can keep uppercase KB vs kB
// ISC License (c) Dan Flettre https://github.com/Flet/prettier-bytes/blob/master/LICENSE
module.exports = function prettierBytes (num) {
  if (typeof num !== 'number' || isNaN(num)) {
    throw new TypeError('Expected a number, got ' + typeof num)
  }

  var neg = num < 0
  var units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB']

  if (neg) {
    num = -num
  }

  if (num < 1) {
    return (neg ? '-' : '') + num + ' B'
  }

  var exponent = Math.min(Math.floor(Math.log(num) / Math.log(1024)), units.length - 1)
  num = Number(num / Math.pow(1024, exponent))
  var unit = units[exponent]

  if (num >= 10 || num % 1 === 0) {
    // Do not show decimals when the number is two-digit, or if the number has no
    // decimal component.
    return (neg ? '-' : '') + num.toFixed(0) + ' ' + unit
  } else {
    return (neg ? '-' : '') + num.toFixed(1) + ' ' + unit
  }
}


/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/core/node_modules/@transloadit/prettier-bytes/prettierBytes.js":
/*!***********************************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/core/node_modules/@transloadit/prettier-bytes/prettierBytes.js ***!
  \***********************************************************************************************************/
/***/ ((module) => {

// Adapted from https://github.com/Flet/prettier-bytes/
// Changing 1000 bytes to 1024, so we can keep uppercase KB vs kB
// ISC License (c) Dan Flettre https://github.com/Flet/prettier-bytes/blob/master/LICENSE
module.exports = function prettierBytes (num) {
  if (typeof num !== 'number' || isNaN(num)) {
    throw new TypeError(`Expected a number, got ${typeof num}`)
  }

  const neg = num < 0
  const units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB']

  if (neg) {
    num = -num
  }

  if (num < 1) {
    return `${(neg ? '-' : '') + num} B`
  }

  const exponent = Math.min(Math.floor(Math.log(num) / Math.log(1024)), units.length - 1)
  num = Number(num / Math.pow(1024, exponent))
  const unit = units[exponent]

  if (num >= 10 || num % 1 === 0) {
    // Do not show decimals when the number is two-digit, or if the number has no
    // decimal component.
    return `${(neg ? '-' : '') + num.toFixed(0)} ${unit}`
  }
  return `${(neg ? '-' : '') + num.toFixed(1)} ${unit}`
}


/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/status-bar/node_modules/@transloadit/prettier-bytes/prettierBytes.js":
/*!*****************************************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/status-bar/node_modules/@transloadit/prettier-bytes/prettierBytes.js ***!
  \*****************************************************************************************************************/
/***/ ((module) => {

// Adapted from https://github.com/Flet/prettier-bytes/
// Changing 1000 bytes to 1024, so we can keep uppercase KB vs kB
// ISC License (c) Dan Flettre https://github.com/Flet/prettier-bytes/blob/master/LICENSE
module.exports = function prettierBytes (num) {
  if (typeof num !== 'number' || isNaN(num)) {
    throw new TypeError(`Expected a number, got ${typeof num}`)
  }

  const neg = num < 0
  const units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB']

  if (neg) {
    num = -num
  }

  if (num < 1) {
    return `${(neg ? '-' : '') + num} B`
  }

  const exponent = Math.min(Math.floor(Math.log(num) / Math.log(1024)), units.length - 1)
  num = Number(num / Math.pow(1024, exponent))
  const unit = units[exponent]

  if (num >= 10 || num % 1 === 0) {
    // Do not show decimals when the number is two-digit, or if the number has no
    // decimal component.
    return `${(neg ? '-' : '') + num.toFixed(0)} ${unit}`
  }
  return `${(neg ? '-' : '') + num.toFixed(1)} ${unit}`
}


/***/ }),

/***/ "./src/jgDashboard/node_modules/classnames/index.js":
/*!**********************************************************!*\
  !*** ./src/jgDashboard/node_modules/classnames/index.js ***!
  \**********************************************************/
/***/ ((module, exports) => {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;/*!
	Copyright (c) 2018 Jed Watson.
	Licensed under the MIT License (MIT), see
	http://jedwatson.github.io/classnames
*/
/* global define */

(function () {
	'use strict';

	var hasOwn = {}.hasOwnProperty;
	var nativeCodeString = '[native code]';

	function classNames() {
		var classes = [];

		for (var i = 0; i < arguments.length; i++) {
			var arg = arguments[i];
			if (!arg) continue;

			var argType = typeof arg;

			if (argType === 'string' || argType === 'number') {
				classes.push(arg);
			} else if (Array.isArray(arg)) {
				if (arg.length) {
					var inner = classNames.apply(null, arg);
					if (inner) {
						classes.push(inner);
					}
				}
			} else if (argType === 'object') {
				if (arg.toString !== Object.prototype.toString && !arg.toString.toString().includes('[native code]')) {
					classes.push(arg.toString());
					continue;
				}

				for (var key in arg) {
					if (hasOwn.call(arg, key) && arg[key]) {
						classes.push(key);
					}
				}
			}
		}

		return classes.join(' ');
	}

	if ( true && module.exports) {
		classNames.default = classNames;
		module.exports = classNames;
	} else if (true) {
		// register as 'classnames', consistent with npm package name
		!(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
			return classNames;
		}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
	} else {}
}());


/***/ }),

/***/ "./src/jgDashboard/node_modules/is-shallow-equal/index.js":
/*!****************************************************************!*\
  !*** ./src/jgDashboard/node_modules/is-shallow-equal/index.js ***!
  \****************************************************************/
/***/ ((module) => {

module.exports = function isShallowEqual (a, b) {
  if (a === b) return true
  for (var i in a) if (!(i in b)) return false
  for (var i in b) if (a[i] !== b[i]) return false
  return true
}


/***/ }),

/***/ "./src/jgDashboard/node_modules/lodash.debounce/index.js":
/*!***************************************************************!*\
  !*** ./src/jgDashboard/node_modules/lodash.debounce/index.js ***!
  \***************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

/**
 * lodash (Custom Build) <https://lodash.com/>
 * Build: `lodash modularize exports="npm" -o ./`
 * Copyright jQuery Foundation and other contributors <https://jquery.org/>
 * Released under MIT license <https://lodash.com/license>
 * Based on Underscore.js 1.8.3 <http://underscorejs.org/LICENSE>
 * Copyright Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors
 */

/** Used as the `TypeError` message for "Functions" methods. */
var FUNC_ERROR_TEXT = 'Expected a function';

/** Used as references for various `Number` constants. */
var NAN = 0 / 0;

/** `Object#toString` result references. */
var symbolTag = '[object Symbol]';

/** Used to match leading and trailing whitespace. */
var reTrim = /^\s+|\s+$/g;

/** Used to detect bad signed hexadecimal string values. */
var reIsBadHex = /^[-+]0x[0-9a-f]+$/i;

/** Used to detect binary string values. */
var reIsBinary = /^0b[01]+$/i;

/** Used to detect octal string values. */
var reIsOctal = /^0o[0-7]+$/i;

/** Built-in method references without a dependency on `root`. */
var freeParseInt = parseInt;

/** Detect free variable `global` from Node.js. */
var freeGlobal = typeof __webpack_require__.g == 'object' && __webpack_require__.g && __webpack_require__.g.Object === Object && __webpack_require__.g;

/** Detect free variable `self`. */
var freeSelf = typeof self == 'object' && self && self.Object === Object && self;

/** Used as a reference to the global object. */
var root = freeGlobal || freeSelf || Function('return this')();

/** Used for built-in method references. */
var objectProto = Object.prototype;

/**
 * Used to resolve the
 * [`toStringTag`](http://ecma-international.org/ecma-262/7.0/#sec-object.prototype.tostring)
 * of values.
 */
var objectToString = objectProto.toString;

/* Built-in method references for those with the same name as other `lodash` methods. */
var nativeMax = Math.max,
    nativeMin = Math.min;

/**
 * Gets the timestamp of the number of milliseconds that have elapsed since
 * the Unix epoch (1 January 1970 00:00:00 UTC).
 *
 * @static
 * @memberOf _
 * @since 2.4.0
 * @category Date
 * @returns {number} Returns the timestamp.
 * @example
 *
 * _.defer(function(stamp) {
 *   console.log(_.now() - stamp);
 * }, _.now());
 * // => Logs the number of milliseconds it took for the deferred invocation.
 */
var now = function() {
  return root.Date.now();
};

/**
 * Creates a debounced function that delays invoking `func` until after `wait`
 * milliseconds have elapsed since the last time the debounced function was
 * invoked. The debounced function comes with a `cancel` method to cancel
 * delayed `func` invocations and a `flush` method to immediately invoke them.
 * Provide `options` to indicate whether `func` should be invoked on the
 * leading and/or trailing edge of the `wait` timeout. The `func` is invoked
 * with the last arguments provided to the debounced function. Subsequent
 * calls to the debounced function return the result of the last `func`
 * invocation.
 *
 * **Note:** If `leading` and `trailing` options are `true`, `func` is
 * invoked on the trailing edge of the timeout only if the debounced function
 * is invoked more than once during the `wait` timeout.
 *
 * If `wait` is `0` and `leading` is `false`, `func` invocation is deferred
 * until to the next tick, similar to `setTimeout` with a timeout of `0`.
 *
 * See [David Corbacho's article](https://css-tricks.com/debouncing-throttling-explained-examples/)
 * for details over the differences between `_.debounce` and `_.throttle`.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Function
 * @param {Function} func The function to debounce.
 * @param {number} [wait=0] The number of milliseconds to delay.
 * @param {Object} [options={}] The options object.
 * @param {boolean} [options.leading=false]
 *  Specify invoking on the leading edge of the timeout.
 * @param {number} [options.maxWait]
 *  The maximum time `func` is allowed to be delayed before it's invoked.
 * @param {boolean} [options.trailing=true]
 *  Specify invoking on the trailing edge of the timeout.
 * @returns {Function} Returns the new debounced function.
 * @example
 *
 * // Avoid costly calculations while the window size is in flux.
 * jQuery(window).on('resize', _.debounce(calculateLayout, 150));
 *
 * // Invoke `sendMail` when clicked, debouncing subsequent calls.
 * jQuery(element).on('click', _.debounce(sendMail, 300, {
 *   'leading': true,
 *   'trailing': false
 * }));
 *
 * // Ensure `batchLog` is invoked once after 1 second of debounced calls.
 * var debounced = _.debounce(batchLog, 250, { 'maxWait': 1000 });
 * var source = new EventSource('/stream');
 * jQuery(source).on('message', debounced);
 *
 * // Cancel the trailing debounced invocation.
 * jQuery(window).on('popstate', debounced.cancel);
 */
function debounce(func, wait, options) {
  var lastArgs,
      lastThis,
      maxWait,
      result,
      timerId,
      lastCallTime,
      lastInvokeTime = 0,
      leading = false,
      maxing = false,
      trailing = true;

  if (typeof func != 'function') {
    throw new TypeError(FUNC_ERROR_TEXT);
  }
  wait = toNumber(wait) || 0;
  if (isObject(options)) {
    leading = !!options.leading;
    maxing = 'maxWait' in options;
    maxWait = maxing ? nativeMax(toNumber(options.maxWait) || 0, wait) : maxWait;
    trailing = 'trailing' in options ? !!options.trailing : trailing;
  }

  function invokeFunc(time) {
    var args = lastArgs,
        thisArg = lastThis;

    lastArgs = lastThis = undefined;
    lastInvokeTime = time;
    result = func.apply(thisArg, args);
    return result;
  }

  function leadingEdge(time) {
    // Reset any `maxWait` timer.
    lastInvokeTime = time;
    // Start the timer for the trailing edge.
    timerId = setTimeout(timerExpired, wait);
    // Invoke the leading edge.
    return leading ? invokeFunc(time) : result;
  }

  function remainingWait(time) {
    var timeSinceLastCall = time - lastCallTime,
        timeSinceLastInvoke = time - lastInvokeTime,
        result = wait - timeSinceLastCall;

    return maxing ? nativeMin(result, maxWait - timeSinceLastInvoke) : result;
  }

  function shouldInvoke(time) {
    var timeSinceLastCall = time - lastCallTime,
        timeSinceLastInvoke = time - lastInvokeTime;

    // Either this is the first call, activity has stopped and we're at the
    // trailing edge, the system time has gone backwards and we're treating
    // it as the trailing edge, or we've hit the `maxWait` limit.
    return (lastCallTime === undefined || (timeSinceLastCall >= wait) ||
      (timeSinceLastCall < 0) || (maxing && timeSinceLastInvoke >= maxWait));
  }

  function timerExpired() {
    var time = now();
    if (shouldInvoke(time)) {
      return trailingEdge(time);
    }
    // Restart the timer.
    timerId = setTimeout(timerExpired, remainingWait(time));
  }

  function trailingEdge(time) {
    timerId = undefined;

    // Only invoke if we have `lastArgs` which means `func` has been
    // debounced at least once.
    if (trailing && lastArgs) {
      return invokeFunc(time);
    }
    lastArgs = lastThis = undefined;
    return result;
  }

  function cancel() {
    if (timerId !== undefined) {
      clearTimeout(timerId);
    }
    lastInvokeTime = 0;
    lastArgs = lastCallTime = lastThis = timerId = undefined;
  }

  function flush() {
    return timerId === undefined ? result : trailingEdge(now());
  }

  function debounced() {
    var time = now(),
        isInvoking = shouldInvoke(time);

    lastArgs = arguments;
    lastThis = this;
    lastCallTime = time;

    if (isInvoking) {
      if (timerId === undefined) {
        return leadingEdge(lastCallTime);
      }
      if (maxing) {
        // Handle invocations in a tight loop.
        timerId = setTimeout(timerExpired, wait);
        return invokeFunc(lastCallTime);
      }
    }
    if (timerId === undefined) {
      timerId = setTimeout(timerExpired, wait);
    }
    return result;
  }
  debounced.cancel = cancel;
  debounced.flush = flush;
  return debounced;
}

/**
 * Checks if `value` is the
 * [language type](http://www.ecma-international.org/ecma-262/7.0/#sec-ecmascript-language-types)
 * of `Object`. (e.g. arrays, functions, objects, regexes, `new Number(0)`, and `new String('')`)
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an object, else `false`.
 * @example
 *
 * _.isObject({});
 * // => true
 *
 * _.isObject([1, 2, 3]);
 * // => true
 *
 * _.isObject(_.noop);
 * // => true
 *
 * _.isObject(null);
 * // => false
 */
function isObject(value) {
  var type = typeof value;
  return !!value && (type == 'object' || type == 'function');
}

/**
 * Checks if `value` is object-like. A value is object-like if it's not `null`
 * and has a `typeof` result of "object".
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is object-like, else `false`.
 * @example
 *
 * _.isObjectLike({});
 * // => true
 *
 * _.isObjectLike([1, 2, 3]);
 * // => true
 *
 * _.isObjectLike(_.noop);
 * // => false
 *
 * _.isObjectLike(null);
 * // => false
 */
function isObjectLike(value) {
  return !!value && typeof value == 'object';
}

/**
 * Checks if `value` is classified as a `Symbol` primitive or object.
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a symbol, else `false`.
 * @example
 *
 * _.isSymbol(Symbol.iterator);
 * // => true
 *
 * _.isSymbol('abc');
 * // => false
 */
function isSymbol(value) {
  return typeof value == 'symbol' ||
    (isObjectLike(value) && objectToString.call(value) == symbolTag);
}

/**
 * Converts `value` to a number.
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to process.
 * @returns {number} Returns the number.
 * @example
 *
 * _.toNumber(3.2);
 * // => 3.2
 *
 * _.toNumber(Number.MIN_VALUE);
 * // => 5e-324
 *
 * _.toNumber(Infinity);
 * // => Infinity
 *
 * _.toNumber('3.2');
 * // => 3.2
 */
function toNumber(value) {
  if (typeof value == 'number') {
    return value;
  }
  if (isSymbol(value)) {
    return NAN;
  }
  if (isObject(value)) {
    var other = typeof value.valueOf == 'function' ? value.valueOf() : value;
    value = isObject(other) ? (other + '') : other;
  }
  if (typeof value != 'string') {
    return value === 0 ? value : +value;
  }
  value = value.replace(reTrim, '');
  var isBinary = reIsBinary.test(value);
  return (isBinary || reIsOctal.test(value))
    ? freeParseInt(value.slice(2), isBinary ? 2 : 8)
    : (reIsBadHex.test(value) ? NAN : +value);
}

module.exports = debounce;


/***/ }),

/***/ "./src/jgDashboard/node_modules/lodash.throttle/index.js":
/*!***************************************************************!*\
  !*** ./src/jgDashboard/node_modules/lodash.throttle/index.js ***!
  \***************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

/**
 * lodash (Custom Build) <https://lodash.com/>
 * Build: `lodash modularize exports="npm" -o ./`
 * Copyright jQuery Foundation and other contributors <https://jquery.org/>
 * Released under MIT license <https://lodash.com/license>
 * Based on Underscore.js 1.8.3 <http://underscorejs.org/LICENSE>
 * Copyright Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors
 */

/** Used as the `TypeError` message for "Functions" methods. */
var FUNC_ERROR_TEXT = 'Expected a function';

/** Used as references for various `Number` constants. */
var NAN = 0 / 0;

/** `Object#toString` result references. */
var symbolTag = '[object Symbol]';

/** Used to match leading and trailing whitespace. */
var reTrim = /^\s+|\s+$/g;

/** Used to detect bad signed hexadecimal string values. */
var reIsBadHex = /^[-+]0x[0-9a-f]+$/i;

/** Used to detect binary string values. */
var reIsBinary = /^0b[01]+$/i;

/** Used to detect octal string values. */
var reIsOctal = /^0o[0-7]+$/i;

/** Built-in method references without a dependency on `root`. */
var freeParseInt = parseInt;

/** Detect free variable `global` from Node.js. */
var freeGlobal = typeof __webpack_require__.g == 'object' && __webpack_require__.g && __webpack_require__.g.Object === Object && __webpack_require__.g;

/** Detect free variable `self`. */
var freeSelf = typeof self == 'object' && self && self.Object === Object && self;

/** Used as a reference to the global object. */
var root = freeGlobal || freeSelf || Function('return this')();

/** Used for built-in method references. */
var objectProto = Object.prototype;

/**
 * Used to resolve the
 * [`toStringTag`](http://ecma-international.org/ecma-262/7.0/#sec-object.prototype.tostring)
 * of values.
 */
var objectToString = objectProto.toString;

/* Built-in method references for those with the same name as other `lodash` methods. */
var nativeMax = Math.max,
    nativeMin = Math.min;

/**
 * Gets the timestamp of the number of milliseconds that have elapsed since
 * the Unix epoch (1 January 1970 00:00:00 UTC).
 *
 * @static
 * @memberOf _
 * @since 2.4.0
 * @category Date
 * @returns {number} Returns the timestamp.
 * @example
 *
 * _.defer(function(stamp) {
 *   console.log(_.now() - stamp);
 * }, _.now());
 * // => Logs the number of milliseconds it took for the deferred invocation.
 */
var now = function() {
  return root.Date.now();
};

/**
 * Creates a debounced function that delays invoking `func` until after `wait`
 * milliseconds have elapsed since the last time the debounced function was
 * invoked. The debounced function comes with a `cancel` method to cancel
 * delayed `func` invocations and a `flush` method to immediately invoke them.
 * Provide `options` to indicate whether `func` should be invoked on the
 * leading and/or trailing edge of the `wait` timeout. The `func` is invoked
 * with the last arguments provided to the debounced function. Subsequent
 * calls to the debounced function return the result of the last `func`
 * invocation.
 *
 * **Note:** If `leading` and `trailing` options are `true`, `func` is
 * invoked on the trailing edge of the timeout only if the debounced function
 * is invoked more than once during the `wait` timeout.
 *
 * If `wait` is `0` and `leading` is `false`, `func` invocation is deferred
 * until to the next tick, similar to `setTimeout` with a timeout of `0`.
 *
 * See [David Corbacho's article](https://css-tricks.com/debouncing-throttling-explained-examples/)
 * for details over the differences between `_.debounce` and `_.throttle`.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Function
 * @param {Function} func The function to debounce.
 * @param {number} [wait=0] The number of milliseconds to delay.
 * @param {Object} [options={}] The options object.
 * @param {boolean} [options.leading=false]
 *  Specify invoking on the leading edge of the timeout.
 * @param {number} [options.maxWait]
 *  The maximum time `func` is allowed to be delayed before it's invoked.
 * @param {boolean} [options.trailing=true]
 *  Specify invoking on the trailing edge of the timeout.
 * @returns {Function} Returns the new debounced function.
 * @example
 *
 * // Avoid costly calculations while the window size is in flux.
 * jQuery(window).on('resize', _.debounce(calculateLayout, 150));
 *
 * // Invoke `sendMail` when clicked, debouncing subsequent calls.
 * jQuery(element).on('click', _.debounce(sendMail, 300, {
 *   'leading': true,
 *   'trailing': false
 * }));
 *
 * // Ensure `batchLog` is invoked once after 1 second of debounced calls.
 * var debounced = _.debounce(batchLog, 250, { 'maxWait': 1000 });
 * var source = new EventSource('/stream');
 * jQuery(source).on('message', debounced);
 *
 * // Cancel the trailing debounced invocation.
 * jQuery(window).on('popstate', debounced.cancel);
 */
function debounce(func, wait, options) {
  var lastArgs,
      lastThis,
      maxWait,
      result,
      timerId,
      lastCallTime,
      lastInvokeTime = 0,
      leading = false,
      maxing = false,
      trailing = true;

  if (typeof func != 'function') {
    throw new TypeError(FUNC_ERROR_TEXT);
  }
  wait = toNumber(wait) || 0;
  if (isObject(options)) {
    leading = !!options.leading;
    maxing = 'maxWait' in options;
    maxWait = maxing ? nativeMax(toNumber(options.maxWait) || 0, wait) : maxWait;
    trailing = 'trailing' in options ? !!options.trailing : trailing;
  }

  function invokeFunc(time) {
    var args = lastArgs,
        thisArg = lastThis;

    lastArgs = lastThis = undefined;
    lastInvokeTime = time;
    result = func.apply(thisArg, args);
    return result;
  }

  function leadingEdge(time) {
    // Reset any `maxWait` timer.
    lastInvokeTime = time;
    // Start the timer for the trailing edge.
    timerId = setTimeout(timerExpired, wait);
    // Invoke the leading edge.
    return leading ? invokeFunc(time) : result;
  }

  function remainingWait(time) {
    var timeSinceLastCall = time - lastCallTime,
        timeSinceLastInvoke = time - lastInvokeTime,
        result = wait - timeSinceLastCall;

    return maxing ? nativeMin(result, maxWait - timeSinceLastInvoke) : result;
  }

  function shouldInvoke(time) {
    var timeSinceLastCall = time - lastCallTime,
        timeSinceLastInvoke = time - lastInvokeTime;

    // Either this is the first call, activity has stopped and we're at the
    // trailing edge, the system time has gone backwards and we're treating
    // it as the trailing edge, or we've hit the `maxWait` limit.
    return (lastCallTime === undefined || (timeSinceLastCall >= wait) ||
      (timeSinceLastCall < 0) || (maxing && timeSinceLastInvoke >= maxWait));
  }

  function timerExpired() {
    var time = now();
    if (shouldInvoke(time)) {
      return trailingEdge(time);
    }
    // Restart the timer.
    timerId = setTimeout(timerExpired, remainingWait(time));
  }

  function trailingEdge(time) {
    timerId = undefined;

    // Only invoke if we have `lastArgs` which means `func` has been
    // debounced at least once.
    if (trailing && lastArgs) {
      return invokeFunc(time);
    }
    lastArgs = lastThis = undefined;
    return result;
  }

  function cancel() {
    if (timerId !== undefined) {
      clearTimeout(timerId);
    }
    lastInvokeTime = 0;
    lastArgs = lastCallTime = lastThis = timerId = undefined;
  }

  function flush() {
    return timerId === undefined ? result : trailingEdge(now());
  }

  function debounced() {
    var time = now(),
        isInvoking = shouldInvoke(time);

    lastArgs = arguments;
    lastThis = this;
    lastCallTime = time;

    if (isInvoking) {
      if (timerId === undefined) {
        return leadingEdge(lastCallTime);
      }
      if (maxing) {
        // Handle invocations in a tight loop.
        timerId = setTimeout(timerExpired, wait);
        return invokeFunc(lastCallTime);
      }
    }
    if (timerId === undefined) {
      timerId = setTimeout(timerExpired, wait);
    }
    return result;
  }
  debounced.cancel = cancel;
  debounced.flush = flush;
  return debounced;
}

/**
 * Creates a throttled function that only invokes `func` at most once per
 * every `wait` milliseconds. The throttled function comes with a `cancel`
 * method to cancel delayed `func` invocations and a `flush` method to
 * immediately invoke them. Provide `options` to indicate whether `func`
 * should be invoked on the leading and/or trailing edge of the `wait`
 * timeout. The `func` is invoked with the last arguments provided to the
 * throttled function. Subsequent calls to the throttled function return the
 * result of the last `func` invocation.
 *
 * **Note:** If `leading` and `trailing` options are `true`, `func` is
 * invoked on the trailing edge of the timeout only if the throttled function
 * is invoked more than once during the `wait` timeout.
 *
 * If `wait` is `0` and `leading` is `false`, `func` invocation is deferred
 * until to the next tick, similar to `setTimeout` with a timeout of `0`.
 *
 * See [David Corbacho's article](https://css-tricks.com/debouncing-throttling-explained-examples/)
 * for details over the differences between `_.throttle` and `_.debounce`.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Function
 * @param {Function} func The function to throttle.
 * @param {number} [wait=0] The number of milliseconds to throttle invocations to.
 * @param {Object} [options={}] The options object.
 * @param {boolean} [options.leading=true]
 *  Specify invoking on the leading edge of the timeout.
 * @param {boolean} [options.trailing=true]
 *  Specify invoking on the trailing edge of the timeout.
 * @returns {Function} Returns the new throttled function.
 * @example
 *
 * // Avoid excessively updating the position while scrolling.
 * jQuery(window).on('scroll', _.throttle(updatePosition, 100));
 *
 * // Invoke `renewToken` when the click event is fired, but not more than once every 5 minutes.
 * var throttled = _.throttle(renewToken, 300000, { 'trailing': false });
 * jQuery(element).on('click', throttled);
 *
 * // Cancel the trailing throttled invocation.
 * jQuery(window).on('popstate', throttled.cancel);
 */
function throttle(func, wait, options) {
  var leading = true,
      trailing = true;

  if (typeof func != 'function') {
    throw new TypeError(FUNC_ERROR_TEXT);
  }
  if (isObject(options)) {
    leading = 'leading' in options ? !!options.leading : leading;
    trailing = 'trailing' in options ? !!options.trailing : trailing;
  }
  return debounce(func, wait, {
    'leading': leading,
    'maxWait': wait,
    'trailing': trailing
  });
}

/**
 * Checks if `value` is the
 * [language type](http://www.ecma-international.org/ecma-262/7.0/#sec-ecmascript-language-types)
 * of `Object`. (e.g. arrays, functions, objects, regexes, `new Number(0)`, and `new String('')`)
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an object, else `false`.
 * @example
 *
 * _.isObject({});
 * // => true
 *
 * _.isObject([1, 2, 3]);
 * // => true
 *
 * _.isObject(_.noop);
 * // => true
 *
 * _.isObject(null);
 * // => false
 */
function isObject(value) {
  var type = typeof value;
  return !!value && (type == 'object' || type == 'function');
}

/**
 * Checks if `value` is object-like. A value is object-like if it's not `null`
 * and has a `typeof` result of "object".
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is object-like, else `false`.
 * @example
 *
 * _.isObjectLike({});
 * // => true
 *
 * _.isObjectLike([1, 2, 3]);
 * // => true
 *
 * _.isObjectLike(_.noop);
 * // => false
 *
 * _.isObjectLike(null);
 * // => false
 */
function isObjectLike(value) {
  return !!value && typeof value == 'object';
}

/**
 * Checks if `value` is classified as a `Symbol` primitive or object.
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a symbol, else `false`.
 * @example
 *
 * _.isSymbol(Symbol.iterator);
 * // => true
 *
 * _.isSymbol('abc');
 * // => false
 */
function isSymbol(value) {
  return typeof value == 'symbol' ||
    (isObjectLike(value) && objectToString.call(value) == symbolTag);
}

/**
 * Converts `value` to a number.
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to process.
 * @returns {number} Returns the number.
 * @example
 *
 * _.toNumber(3.2);
 * // => 3.2
 *
 * _.toNumber(Number.MIN_VALUE);
 * // => 5e-324
 *
 * _.toNumber(Infinity);
 * // => Infinity
 *
 * _.toNumber('3.2');
 * // => 3.2
 */
function toNumber(value) {
  if (typeof value == 'number') {
    return value;
  }
  if (isSymbol(value)) {
    return NAN;
  }
  if (isObject(value)) {
    var other = typeof value.valueOf == 'function' ? value.valueOf() : value;
    value = isObject(other) ? (other + '') : other;
  }
  if (typeof value != 'string') {
    return value === 0 ? value : +value;
  }
  value = value.replace(reTrim, '');
  var isBinary = reIsBinary.test(value);
  return (isBinary || reIsOctal.test(value))
    ? freeParseInt(value.slice(2), isBinary ? 2 : 8)
    : (reIsBadHex.test(value) ? NAN : +value);
}

module.exports = throttle;


/***/ }),

/***/ "./src/jgDashboard/node_modules/memoize-one/dist/memoize-one.esm.js":
/*!**************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/memoize-one/dist/memoize-one.esm.js ***!
  \**************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ memoizeOne)
/* harmony export */ });
var safeIsNaN = Number.isNaN ||
    function ponyfill(value) {
        return typeof value === 'number' && value !== value;
    };
function isEqual(first, second) {
    if (first === second) {
        return true;
    }
    if (safeIsNaN(first) && safeIsNaN(second)) {
        return true;
    }
    return false;
}
function areInputsEqual(newInputs, lastInputs) {
    if (newInputs.length !== lastInputs.length) {
        return false;
    }
    for (var i = 0; i < newInputs.length; i++) {
        if (!isEqual(newInputs[i], lastInputs[i])) {
            return false;
        }
    }
    return true;
}

function memoizeOne(resultFn, isEqual) {
    if (isEqual === void 0) { isEqual = areInputsEqual; }
    var cache = null;
    function memoized() {
        var newArgs = [];
        for (var _i = 0; _i < arguments.length; _i++) {
            newArgs[_i] = arguments[_i];
        }
        if (cache && cache.lastThis === this && isEqual(newArgs, cache.lastArgs)) {
            return cache.lastResult;
        }
        var lastResult = resultFn.apply(this, newArgs);
        cache = {
            lastResult: lastResult,
            lastArgs: newArgs,
            lastThis: this,
        };
        return lastResult;
    }
    memoized.clear = function clear() {
        cache = null;
    };
    return memoized;
}




/***/ }),

/***/ "./src/jgDashboard/node_modules/mime-match/index.js":
/*!**********************************************************!*\
  !*** ./src/jgDashboard/node_modules/mime-match/index.js ***!
  \**********************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

var wildcard = __webpack_require__(/*! wildcard */ "./src/jgDashboard/node_modules/wildcard/index.js");
var reMimePartSplit = /[\/\+\.]/;

/**
  # mime-match

  A simple function to checker whether a target mime type matches a mime-type
  pattern (e.g. image/jpeg matches image/jpeg OR image/*).

  ## Example Usage

  <<< example.js

**/
module.exports = function(target, pattern) {
  function test(pattern) {
    var result = wildcard(pattern, target, reMimePartSplit);

    // ensure that we have a valid mime type (should have two parts)
    return result && result.length >= 2;
  }

  return pattern ? test(pattern.split(';')[0]) : test;
};


/***/ }),

/***/ "./src/jgDashboard/node_modules/namespace-emitter/index.js":
/*!*****************************************************************!*\
  !*** ./src/jgDashboard/node_modules/namespace-emitter/index.js ***!
  \*****************************************************************/
/***/ ((module) => {

/**
* Create an event emitter with namespaces
* @name createNamespaceEmitter
* @example
* var emitter = require('./index')()
*
* emitter.on('*', function () {
*   console.log('all events emitted', this.event)
* })
*
* emitter.on('example', function () {
*   console.log('example event emitted')
* })
*/
module.exports = function createNamespaceEmitter () {
  var emitter = {}
  var _fns = emitter._fns = {}

  /**
  * Emit an event. Optionally namespace the event. Handlers are fired in the order in which they were added with exact matches taking precedence. Separate the namespace and event with a `:`
  * @name emit
  * @param {String} event – the name of the event, with optional namespace
  * @param {...*} data – up to 6 arguments that are passed to the event listener
  * @example
  * emitter.emit('example')
  * emitter.emit('demo:test')
  * emitter.emit('data', { example: true}, 'a string', 1)
  */
  emitter.emit = function emit (event, arg1, arg2, arg3, arg4, arg5, arg6) {
    var toEmit = getListeners(event)

    if (toEmit.length) {
      emitAll(event, toEmit, [arg1, arg2, arg3, arg4, arg5, arg6])
    }
  }

  /**
  * Create en event listener.
  * @name on
  * @param {String} event
  * @param {Function} fn
  * @example
  * emitter.on('example', function () {})
  * emitter.on('demo', function () {})
  */
  emitter.on = function on (event, fn) {
    if (!_fns[event]) {
      _fns[event] = []
    }

    _fns[event].push(fn)
  }

  /**
  * Create en event listener that fires once.
  * @name once
  * @param {String} event
  * @param {Function} fn
  * @example
  * emitter.once('example', function () {})
  * emitter.once('demo', function () {})
  */
  emitter.once = function once (event, fn) {
    function one () {
      fn.apply(this, arguments)
      emitter.off(event, one)
    }
    this.on(event, one)
  }

  /**
  * Stop listening to an event. Stop all listeners on an event by only passing the event name. Stop a single listener by passing that event handler as a callback.
  * You must be explicit about what will be unsubscribed: `emitter.off('demo')` will unsubscribe an `emitter.on('demo')` listener,
  * `emitter.off('demo:example')` will unsubscribe an `emitter.on('demo:example')` listener
  * @name off
  * @param {String} event
  * @param {Function} [fn] – the specific handler
  * @example
  * emitter.off('example')
  * emitter.off('demo', function () {})
  */
  emitter.off = function off (event, fn) {
    var keep = []

    if (event && fn) {
      var fns = this._fns[event]
      var i = 0
      var l = fns ? fns.length : 0

      for (i; i < l; i++) {
        if (fns[i] !== fn) {
          keep.push(fns[i])
        }
      }
    }

    keep.length ? this._fns[event] = keep : delete this._fns[event]
  }

  function getListeners (e) {
    var out = _fns[e] ? _fns[e] : []
    var idx = e.indexOf(':')
    var args = (idx === -1) ? [e] : [e.substring(0, idx), e.substring(idx + 1)]

    var keys = Object.keys(_fns)
    var i = 0
    var l = keys.length

    for (i; i < l; i++) {
      var key = keys[i]
      if (key === '*') {
        out = out.concat(_fns[key])
      }

      if (args.length === 2 && args[0] === key) {
        out = out.concat(_fns[key])
        break
      }
    }

    return out
  }

  function emitAll (e, fns, args) {
    var i = 0
    var l = fns.length

    for (i; i < l; i++) {
      if (!fns[i]) break
      fns[i].event = e
      fns[i].apply(fns[i], args)
    }
  }

  return emitter
}


/***/ }),

/***/ "./src/jgDashboard/node_modules/preact/dist/preact.module.js":
/*!*******************************************************************!*\
  !*** ./src/jgDashboard/node_modules/preact/dist/preact.module.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Component: () => (/* binding */ b),
/* harmony export */   Fragment: () => (/* binding */ k),
/* harmony export */   cloneElement: () => (/* binding */ F),
/* harmony export */   createContext: () => (/* binding */ G),
/* harmony export */   createElement: () => (/* binding */ y),
/* harmony export */   createRef: () => (/* binding */ _),
/* harmony export */   h: () => (/* binding */ y),
/* harmony export */   hydrate: () => (/* binding */ E),
/* harmony export */   isValidElement: () => (/* binding */ i),
/* harmony export */   options: () => (/* binding */ l),
/* harmony export */   render: () => (/* binding */ D),
/* harmony export */   toChildArray: () => (/* binding */ S)
/* harmony export */ });
var n,l,u,i,t,o,r,f,e,c={},s=[],a=/acit|ex(?:s|g|n|p|$)|rph|grid|ows|mnc|ntw|ine[ch]|zoo|^ord|itera/i,v=Array.isArray;function h(n,l){for(var u in l)n[u]=l[u];return n}function p(n){var l=n.parentNode;l&&l.removeChild(n)}function y(l,u,i){var t,o,r,f={};for(r in u)"key"==r?t=u[r]:"ref"==r?o=u[r]:f[r]=u[r];if(arguments.length>2&&(f.children=arguments.length>3?n.call(arguments,2):i),"function"==typeof l&&null!=l.defaultProps)for(r in l.defaultProps)void 0===f[r]&&(f[r]=l.defaultProps[r]);return d(l,f,t,o,null)}function d(n,i,t,o,r){var f={type:n,props:i,key:t,ref:o,__k:null,__:null,__b:0,__e:null,__d:void 0,__c:null,__h:null,constructor:void 0,__v:null==r?++u:r};return null==r&&null!=l.vnode&&l.vnode(f),f}function _(){return{current:null}}function k(n){return n.children}function b(n,l){this.props=n,this.context=l}function g(n,l){if(null==l)return n.__?g(n.__,n.__.__k.indexOf(n)+1):null;for(var u;l<n.__k.length;l++)if(null!=(u=n.__k[l])&&null!=u.__e)return u.__e;return"function"==typeof n.type?g(n):null}function m(n){var l,u;if(null!=(n=n.__)&&null!=n.__c){for(n.__e=n.__c.base=null,l=0;l<n.__k.length;l++)if(null!=(u=n.__k[l])&&null!=u.__e){n.__e=n.__c.base=u.__e;break}return m(n)}}function w(n){(!n.__d&&(n.__d=!0)&&t.push(n)&&!x.__r++||o!==l.debounceRendering)&&((o=l.debounceRendering)||r)(x)}function x(){var n,l,u,i,o,r,e,c;for(t.sort(f);n=t.shift();)n.__d&&(l=t.length,i=void 0,o=void 0,e=(r=(u=n).__v).__e,(c=u.__P)&&(i=[],(o=h({},r)).__v=r.__v+1,L(c,r,o,u.__n,void 0!==c.ownerSVGElement,null!=r.__h?[e]:null,i,null==e?g(r):e,r.__h),M(i,r),r.__e!=e&&m(r)),t.length>l&&t.sort(f));x.__r=0}function P(n,l,u,i,t,o,r,f,e,a){var h,p,y,_,b,m,w,x=i&&i.__k||s,P=x.length;for(u.__k=[],h=0;h<l.length;h++)if(null!=(_=u.__k[h]=null==(_=l[h])||"boolean"==typeof _||"function"==typeof _?null:"string"==typeof _||"number"==typeof _||"bigint"==typeof _?d(null,_,null,null,_):v(_)?d(k,{children:_},null,null,null):_.__b>0?d(_.type,_.props,_.key,_.ref?_.ref:null,_.__v):_)){if(_.__=u,_.__b=u.__b+1,null===(y=x[h])||y&&_.key==y.key&&_.type===y.type)x[h]=void 0;else for(p=0;p<P;p++){if((y=x[p])&&_.key==y.key&&_.type===y.type){x[p]=void 0;break}y=null}L(n,_,y=y||c,t,o,r,f,e,a),b=_.__e,(p=_.ref)&&y.ref!=p&&(w||(w=[]),y.ref&&w.push(y.ref,null,_),w.push(p,_.__c||b,_)),null!=b?(null==m&&(m=b),"function"==typeof _.type&&_.__k===y.__k?_.__d=e=C(_,e,n):e=$(n,_,y,x,b,e),"function"==typeof u.type&&(u.__d=e)):e&&y.__e==e&&e.parentNode!=n&&(e=g(y))}for(u.__e=m,h=P;h--;)null!=x[h]&&("function"==typeof u.type&&null!=x[h].__e&&x[h].__e==u.__d&&(u.__d=A(i).nextSibling),q(x[h],x[h]));if(w)for(h=0;h<w.length;h++)O(w[h],w[++h],w[++h])}function C(n,l,u){for(var i,t=n.__k,o=0;t&&o<t.length;o++)(i=t[o])&&(i.__=n,l="function"==typeof i.type?C(i,l,u):$(u,i,i,t,i.__e,l));return l}function S(n,l){return l=l||[],null==n||"boolean"==typeof n||(v(n)?n.some(function(n){S(n,l)}):l.push(n)),l}function $(n,l,u,i,t,o){var r,f,e;if(void 0!==l.__d)r=l.__d,l.__d=void 0;else if(null==u||t!=o||null==t.parentNode)n:if(null==o||o.parentNode!==n)n.appendChild(t),r=null;else{for(f=o,e=0;(f=f.nextSibling)&&e<i.length;e+=1)if(f==t)break n;n.insertBefore(t,o),r=o}return void 0!==r?r:t.nextSibling}function A(n){var l,u,i;if(null==n.type||"string"==typeof n.type)return n.__e;if(n.__k)for(l=n.__k.length-1;l>=0;l--)if((u=n.__k[l])&&(i=A(u)))return i;return null}function H(n,l,u,i,t){var o;for(o in u)"children"===o||"key"===o||o in l||T(n,o,null,u[o],i);for(o in l)t&&"function"!=typeof l[o]||"children"===o||"key"===o||"value"===o||"checked"===o||u[o]===l[o]||T(n,o,l[o],u[o],i)}function I(n,l,u){"-"===l[0]?n.setProperty(l,null==u?"":u):n[l]=null==u?"":"number"!=typeof u||a.test(l)?u:u+"px"}function T(n,l,u,i,t){var o;n:if("style"===l)if("string"==typeof u)n.style.cssText=u;else{if("string"==typeof i&&(n.style.cssText=i=""),i)for(l in i)u&&l in u||I(n.style,l,"");if(u)for(l in u)i&&u[l]===i[l]||I(n.style,l,u[l])}else if("o"===l[0]&&"n"===l[1])o=l!==(l=l.replace(/Capture$/,"")),l=l.toLowerCase()in n?l.toLowerCase().slice(2):l.slice(2),n.l||(n.l={}),n.l[l+o]=u,u?i||n.addEventListener(l,o?z:j,o):n.removeEventListener(l,o?z:j,o);else if("dangerouslySetInnerHTML"!==l){if(t)l=l.replace(/xlink(H|:h)/,"h").replace(/sName$/,"s");else if("width"!==l&&"height"!==l&&"href"!==l&&"list"!==l&&"form"!==l&&"tabIndex"!==l&&"download"!==l&&"rowSpan"!==l&&"colSpan"!==l&&l in n)try{n[l]=null==u?"":u;break n}catch(n){}"function"==typeof u||(null==u||!1===u&&"-"!==l[4]?n.removeAttribute(l):n.setAttribute(l,u))}}function j(n){return this.l[n.type+!1](l.event?l.event(n):n)}function z(n){return this.l[n.type+!0](l.event?l.event(n):n)}function L(n,u,i,t,o,r,f,e,c){var s,a,p,y,d,_,g,m,w,x,C,S,$,A,H,I=u.type;if(void 0!==u.constructor)return null;null!=i.__h&&(c=i.__h,e=u.__e=i.__e,u.__h=null,r=[e]),(s=l.__b)&&s(u);try{n:if("function"==typeof I){if(m=u.props,w=(s=I.contextType)&&t[s.__c],x=s?w?w.props.value:s.__:t,i.__c?g=(a=u.__c=i.__c).__=a.__E:("prototype"in I&&I.prototype.render?u.__c=a=new I(m,x):(u.__c=a=new b(m,x),a.constructor=I,a.render=B),w&&w.sub(a),a.props=m,a.state||(a.state={}),a.context=x,a.__n=t,p=a.__d=!0,a.__h=[],a._sb=[]),null==a.__s&&(a.__s=a.state),null!=I.getDerivedStateFromProps&&(a.__s==a.state&&(a.__s=h({},a.__s)),h(a.__s,I.getDerivedStateFromProps(m,a.__s))),y=a.props,d=a.state,a.__v=u,p)null==I.getDerivedStateFromProps&&null!=a.componentWillMount&&a.componentWillMount(),null!=a.componentDidMount&&a.__h.push(a.componentDidMount);else{if(null==I.getDerivedStateFromProps&&m!==y&&null!=a.componentWillReceiveProps&&a.componentWillReceiveProps(m,x),!a.__e&&null!=a.shouldComponentUpdate&&!1===a.shouldComponentUpdate(m,a.__s,x)||u.__v===i.__v){for(u.__v!==i.__v&&(a.props=m,a.state=a.__s,a.__d=!1),a.__e=!1,u.__e=i.__e,u.__k=i.__k,u.__k.forEach(function(n){n&&(n.__=u)}),C=0;C<a._sb.length;C++)a.__h.push(a._sb[C]);a._sb=[],a.__h.length&&f.push(a);break n}null!=a.componentWillUpdate&&a.componentWillUpdate(m,a.__s,x),null!=a.componentDidUpdate&&a.__h.push(function(){a.componentDidUpdate(y,d,_)})}if(a.context=x,a.props=m,a.__P=n,S=l.__r,$=0,"prototype"in I&&I.prototype.render){for(a.state=a.__s,a.__d=!1,S&&S(u),s=a.render(a.props,a.state,a.context),A=0;A<a._sb.length;A++)a.__h.push(a._sb[A]);a._sb=[]}else do{a.__d=!1,S&&S(u),s=a.render(a.props,a.state,a.context),a.state=a.__s}while(a.__d&&++$<25);a.state=a.__s,null!=a.getChildContext&&(t=h(h({},t),a.getChildContext())),p||null==a.getSnapshotBeforeUpdate||(_=a.getSnapshotBeforeUpdate(y,d)),P(n,v(H=null!=s&&s.type===k&&null==s.key?s.props.children:s)?H:[H],u,i,t,o,r,f,e,c),a.base=u.__e,u.__h=null,a.__h.length&&f.push(a),g&&(a.__E=a.__=null),a.__e=!1}else null==r&&u.__v===i.__v?(u.__k=i.__k,u.__e=i.__e):u.__e=N(i.__e,u,i,t,o,r,f,c);(s=l.diffed)&&s(u)}catch(n){u.__v=null,(c||null!=r)&&(u.__e=e,u.__h=!!c,r[r.indexOf(e)]=null),l.__e(n,u,i)}}function M(n,u){l.__c&&l.__c(u,n),n.some(function(u){try{n=u.__h,u.__h=[],n.some(function(n){n.call(u)})}catch(n){l.__e(n,u.__v)}})}function N(l,u,i,t,o,r,f,e){var s,a,h,y=i.props,d=u.props,_=u.type,k=0;if("svg"===_&&(o=!0),null!=r)for(;k<r.length;k++)if((s=r[k])&&"setAttribute"in s==!!_&&(_?s.localName===_:3===s.nodeType)){l=s,r[k]=null;break}if(null==l){if(null===_)return document.createTextNode(d);l=o?document.createElementNS("http://www.w3.org/2000/svg",_):document.createElement(_,d.is&&d),r=null,e=!1}if(null===_)y===d||e&&l.data===d||(l.data=d);else{if(r=r&&n.call(l.childNodes),a=(y=i.props||c).dangerouslySetInnerHTML,h=d.dangerouslySetInnerHTML,!e){if(null!=r)for(y={},k=0;k<l.attributes.length;k++)y[l.attributes[k].name]=l.attributes[k].value;(h||a)&&(h&&(a&&h.__html==a.__html||h.__html===l.innerHTML)||(l.innerHTML=h&&h.__html||""))}if(H(l,d,y,o,e),h)u.__k=[];else if(P(l,v(k=u.props.children)?k:[k],u,i,t,o&&"foreignObject"!==_,r,f,r?r[0]:i.__k&&g(i,0),e),null!=r)for(k=r.length;k--;)null!=r[k]&&p(r[k]);e||("value"in d&&void 0!==(k=d.value)&&(k!==l.value||"progress"===_&&!k||"option"===_&&k!==y.value)&&T(l,"value",k,y.value,!1),"checked"in d&&void 0!==(k=d.checked)&&k!==l.checked&&T(l,"checked",k,y.checked,!1))}return l}function O(n,u,i){try{"function"==typeof n?n(u):n.current=u}catch(n){l.__e(n,i)}}function q(n,u,i){var t,o;if(l.unmount&&l.unmount(n),(t=n.ref)&&(t.current&&t.current!==n.__e||O(t,null,u)),null!=(t=n.__c)){if(t.componentWillUnmount)try{t.componentWillUnmount()}catch(n){l.__e(n,u)}t.base=t.__P=null,n.__c=void 0}if(t=n.__k)for(o=0;o<t.length;o++)t[o]&&q(t[o],u,i||"function"!=typeof n.type);i||null==n.__e||p(n.__e),n.__=n.__e=n.__d=void 0}function B(n,l,u){return this.constructor(n,u)}function D(u,i,t){var o,r,f;l.__&&l.__(u,i),r=(o="function"==typeof t)?null:t&&t.__k||i.__k,f=[],L(i,u=(!o&&t||i).__k=y(k,null,[u]),r||c,c,void 0!==i.ownerSVGElement,!o&&t?[t]:r?null:i.firstChild?n.call(i.childNodes):null,f,!o&&t?t:r?r.__e:i.firstChild,o),M(f,u)}function E(n,l){D(n,l,E)}function F(l,u,i){var t,o,r,f,e=h({},l.props);for(r in l.type&&l.type.defaultProps&&(f=l.type.defaultProps),u)"key"==r?t=u[r]:"ref"==r?o=u[r]:e[r]=void 0===u[r]&&void 0!==f?f[r]:u[r];return arguments.length>2&&(e.children=arguments.length>3?n.call(arguments,2):i),d(l.type,e,t||l.key,o||l.ref,null)}function G(n,l){var u={__c:l="__cC"+e++,__:n,Consumer:function(n,l){return n.children(l)},Provider:function(n){var u,i;return this.getChildContext||(u=[],(i={})[l]=this,this.getChildContext=function(){return i},this.shouldComponentUpdate=function(n){this.props.value!==n.value&&u.some(function(n){n.__e=!0,w(n)})},this.sub=function(n){u.push(n);var l=n.componentWillUnmount;n.componentWillUnmount=function(){u.splice(u.indexOf(n),1),l&&l.call(n)}}),n.children}};return u.Provider.__=u.Consumer.contextType=u}n=s.slice,l={__e:function(n,l,u,i){for(var t,o,r;l=l.__;)if((t=l.__c)&&!t.__)try{if((o=t.constructor)&&null!=o.getDerivedStateFromError&&(t.setState(o.getDerivedStateFromError(n)),r=t.__d),null!=t.componentDidCatch&&(t.componentDidCatch(n,i||{}),r=t.__d),r)return t.__E=t}catch(l){n=l}throw n}},u=0,i=function(n){return null!=n&&void 0===n.constructor},b.prototype.setState=function(n,l){var u;u=null!=this.__s&&this.__s!==this.state?this.__s:this.__s=h({},this.state),"function"==typeof n&&(n=n(h({},u),this.props)),n&&h(u,n),null!=n&&this.__v&&(l&&this._sb.push(l),w(this))},b.prototype.forceUpdate=function(n){this.__v&&(this.__e=!0,n&&this.__h.push(n),w(this))},b.prototype.render=k,t=[],r="function"==typeof Promise?Promise.prototype.then.bind(Promise.resolve()):setTimeout,f=function(n,l){return n.__v.__b-l.__v.__b},x.__r=0,e=0;
//# sourceMappingURL=preact.module.js.map


/***/ }),

/***/ "./src/jgDashboard/node_modules/preact/hooks/dist/hooks.module.js":
/*!************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/preact/hooks/dist/hooks.module.js ***!
  \************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useCallback: () => (/* binding */ T),
/* harmony export */   useContext: () => (/* binding */ q),
/* harmony export */   useDebugValue: () => (/* binding */ x),
/* harmony export */   useEffect: () => (/* binding */ p),
/* harmony export */   useErrorBoundary: () => (/* binding */ P),
/* harmony export */   useId: () => (/* binding */ V),
/* harmony export */   useImperativeHandle: () => (/* binding */ A),
/* harmony export */   useLayoutEffect: () => (/* binding */ y),
/* harmony export */   useMemo: () => (/* binding */ F),
/* harmony export */   useReducer: () => (/* binding */ s),
/* harmony export */   useRef: () => (/* binding */ _),
/* harmony export */   useState: () => (/* binding */ h)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
var t,r,u,i,o=0,f=[],c=[],e=preact__WEBPACK_IMPORTED_MODULE_0__.options.__b,a=preact__WEBPACK_IMPORTED_MODULE_0__.options.__r,v=preact__WEBPACK_IMPORTED_MODULE_0__.options.diffed,l=preact__WEBPACK_IMPORTED_MODULE_0__.options.__c,m=preact__WEBPACK_IMPORTED_MODULE_0__.options.unmount;function d(t,u){preact__WEBPACK_IMPORTED_MODULE_0__.options.__h&&preact__WEBPACK_IMPORTED_MODULE_0__.options.__h(r,t,o||u),o=0;var i=r.__H||(r.__H={__:[],__h:[]});return t>=i.__.length&&i.__.push({__V:c}),i.__[t]}function h(n){return o=1,s(B,n)}function s(n,u,i){var o=d(t++,2);if(o.t=n,!o.__c&&(o.__=[i?i(u):B(void 0,u),function(n){var t=o.__N?o.__N[0]:o.__[0],r=o.t(t,n);t!==r&&(o.__N=[r,o.__[1]],o.__c.setState({}))}],o.__c=r,!r.u)){var f=function(n,t,r){if(!o.__c.__H)return!0;var u=o.__c.__H.__.filter(function(n){return n.__c});if(u.every(function(n){return!n.__N}))return!c||c.call(this,n,t,r);var i=!1;return u.forEach(function(n){if(n.__N){var t=n.__[0];n.__=n.__N,n.__N=void 0,t!==n.__[0]&&(i=!0)}}),!(!i&&o.__c.props===n)&&(!c||c.call(this,n,t,r))};r.u=!0;var c=r.shouldComponentUpdate,e=r.componentWillUpdate;r.componentWillUpdate=function(n,t,r){if(this.__e){var u=c;c=void 0,f(n,t,r),c=u}e&&e.call(this,n,t,r)},r.shouldComponentUpdate=f}return o.__N||o.__}function p(u,i){var o=d(t++,3);!preact__WEBPACK_IMPORTED_MODULE_0__.options.__s&&z(o.__H,i)&&(o.__=u,o.i=i,r.__H.__h.push(o))}function y(u,i){var o=d(t++,4);!preact__WEBPACK_IMPORTED_MODULE_0__.options.__s&&z(o.__H,i)&&(o.__=u,o.i=i,r.__h.push(o))}function _(n){return o=5,F(function(){return{current:n}},[])}function A(n,t,r){o=6,y(function(){return"function"==typeof n?(n(t()),function(){return n(null)}):n?(n.current=t(),function(){return n.current=null}):void 0},null==r?r:r.concat(n))}function F(n,r){var u=d(t++,7);return z(u.__H,r)?(u.__V=n(),u.i=r,u.__h=n,u.__V):u.__}function T(n,t){return o=8,F(function(){return n},t)}function q(n){var u=r.context[n.__c],i=d(t++,9);return i.c=n,u?(null==i.__&&(i.__=!0,u.sub(r)),u.props.value):n.__}function x(t,r){preact__WEBPACK_IMPORTED_MODULE_0__.options.useDebugValue&&preact__WEBPACK_IMPORTED_MODULE_0__.options.useDebugValue(r?r(t):t)}function P(n){var u=d(t++,10),i=h();return u.__=n,r.componentDidCatch||(r.componentDidCatch=function(n,t){u.__&&u.__(n,t),i[1](n)}),[i[0],function(){i[1](void 0)}]}function V(){var n=d(t++,11);if(!n.__){for(var u=r.__v;null!==u&&!u.__m&&null!==u.__;)u=u.__;var i=u.__m||(u.__m=[0,0]);n.__="P"+i[0]+"-"+i[1]++}return n.__}function b(){for(var t;t=f.shift();)if(t.__P&&t.__H)try{t.__H.__h.forEach(k),t.__H.__h.forEach(w),t.__H.__h=[]}catch(r){t.__H.__h=[],preact__WEBPACK_IMPORTED_MODULE_0__.options.__e(r,t.__v)}}preact__WEBPACK_IMPORTED_MODULE_0__.options.__b=function(n){r=null,e&&e(n)},preact__WEBPACK_IMPORTED_MODULE_0__.options.__r=function(n){a&&a(n),t=0;var i=(r=n.__c).__H;i&&(u===r?(i.__h=[],r.__h=[],i.__.forEach(function(n){n.__N&&(n.__=n.__N),n.__V=c,n.__N=n.i=void 0})):(i.__h.forEach(k),i.__h.forEach(w),i.__h=[],t=0)),u=r},preact__WEBPACK_IMPORTED_MODULE_0__.options.diffed=function(t){v&&v(t);var o=t.__c;o&&o.__H&&(o.__H.__h.length&&(1!==f.push(o)&&i===preact__WEBPACK_IMPORTED_MODULE_0__.options.requestAnimationFrame||((i=preact__WEBPACK_IMPORTED_MODULE_0__.options.requestAnimationFrame)||j)(b)),o.__H.__.forEach(function(n){n.i&&(n.__H=n.i),n.__V!==c&&(n.__=n.__V),n.i=void 0,n.__V=c})),u=r=null},preact__WEBPACK_IMPORTED_MODULE_0__.options.__c=function(t,r){r.some(function(t){try{t.__h.forEach(k),t.__h=t.__h.filter(function(n){return!n.__||w(n)})}catch(u){r.some(function(n){n.__h&&(n.__h=[])}),r=[],preact__WEBPACK_IMPORTED_MODULE_0__.options.__e(u,t.__v)}}),l&&l(t,r)},preact__WEBPACK_IMPORTED_MODULE_0__.options.unmount=function(t){m&&m(t);var r,u=t.__c;u&&u.__H&&(u.__H.__.forEach(function(n){try{k(n)}catch(n){r=n}}),u.__H=void 0,r&&preact__WEBPACK_IMPORTED_MODULE_0__.options.__e(r,u.__v))};var g="function"==typeof requestAnimationFrame;function j(n){var t,r=function(){clearTimeout(u),g&&cancelAnimationFrame(t),setTimeout(n)},u=setTimeout(r,100);g&&(t=requestAnimationFrame(r))}function k(n){var t=r,u=n.__c;"function"==typeof u&&(n.__c=void 0,u()),r=t}function w(n){var t=r;n.__c=n.__(),r=t}function z(n,t){return!n||n.length!==t.length||t.some(function(t,r){return t!==n[r]})}function B(n,t){return"function"==typeof t?t(n):t}
//# sourceMappingURL=hooks.module.js.map


/***/ }),

/***/ "./src/jgDashboard/node_modules/wildcard/index.js":
/*!********************************************************!*\
  !*** ./src/jgDashboard/node_modules/wildcard/index.js ***!
  \********************************************************/
/***/ ((module) => {

"use strict";
/* jshint node: true */


/**
  # wildcard

  Very simple wildcard matching, which is designed to provide the same
  functionality that is found in the
  [eve](https://github.com/adobe-webplatform/eve) eventing library.

  ## Usage

  It works with strings:

  <<< examples/strings.js

  Arrays:

  <<< examples/arrays.js

  Objects (matching against keys):

  <<< examples/objects.js

  While the library works in Node, if you are are looking for file-based
  wildcard matching then you should have a look at:

  <https://github.com/isaacs/node-glob>
**/

function WildcardMatcher(text, separator) {
  this.text = text = text || '';
  this.hasWild = ~text.indexOf('*');
  this.separator = separator;
  this.parts = text.split(separator);
}

WildcardMatcher.prototype.match = function(input) {
  var matches = true;
  var parts = this.parts;
  var ii;
  var partsCount = parts.length;
  var testParts;

  if (typeof input == 'string' || input instanceof String) {
    if (!this.hasWild && this.text != input) {
      matches = false;
    } else {
      testParts = (input || '').split(this.separator);
      for (ii = 0; matches && ii < partsCount; ii++) {
        if (parts[ii] === '*')  {
          continue;
        } else if (ii < testParts.length) {
          matches = parts[ii] === testParts[ii];
        } else {
          matches = false;
        }
      }

      // If matches, then return the component parts
      matches = matches && testParts;
    }
  }
  else if (typeof input.splice == 'function') {
    matches = [];

    for (ii = input.length; ii--; ) {
      if (this.match(input[ii])) {
        matches[matches.length] = input[ii];
      }
    }
  }
  else if (typeof input == 'object') {
    matches = {};

    for (var key in input) {
      if (this.match(key)) {
        matches[key] = input[key];
      }
    }
  }

  return matches;
};

module.exports = function(text, test, separator) {
  var matcher = new WildcardMatcher(text, separator || /[\/\.]/);
  if (typeof test != 'undefined') {
    return matcher.match(test);
  }

  return matcher;
};


/***/ }),

/***/ "./src/jgprocessor.js":
/*!****************************!*\
  !*** ./src/jgprocessor.js ***!
  \****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ JGprocessor)
/* harmony export */ });
/* harmony import */ var _uppy_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @uppy/core */ "./node_modules/@uppy/core/lib/index.js");
/* harmony import */ var _uppy_utils_lib_Translator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @uppy/utils/lib/Translator */ "./node_modules/@uppy/utils/lib/Translator.js");
/* harmony import */ var _gullerya_object_observer__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @gullerya/object-observer */ "./node_modules/@gullerya/object-observer/dist/object-observer.min.js");




class JGprocessor extends _uppy_core__WEBPACK_IMPORTED_MODULE_0__.BasePlugin {
  /**
   * Constructor
   * Doc: https://uppy.io/docs/guides/building-plugins/#creating-a-plugin
   *
   * @param   {Object}   uppy	   Uppy instance
   * @param   {Object}   opts	   Options object passed in to the uppy.use()
   *
   * @returns {Viod}
   * @throws Error
   */
  constructor(uppy, opts) {
    super(uppy, opts);
    this.type     = 'saver';
    this.id       = this.opts.id || 'JGProcessor';
    this.title    = 'JGProcessor';
    this.formID   = this.opts.formID || 'adminForm';
    this.uploadID = this.opts.uploadID || '';

    // Bind this to plugin functions
    this.awaitSaveRequest = this.awaitSaveRequest.bind(this);
    this.startSaveRequest = this.startSaveRequest.bind(this);
    this.sendFetchRequest = this.sendFetchRequest.bind(this);
    this.prepareProcess   = this.prepareProcess.bind(this);
    this.handleError      = this.handleError.bind(this);
    this.setFileError     = this.setFileError.bind(this);
    this.setFileSuccess   = this.setFileSuccess.bind(this);
    this.addDebugBtn      = this.addDebugBtn.bind(this);
    this.addStateTxt      = this.addStateTxt.bind(this);
    this.addTitle         = this.addTitle.bind(this);    

    // Define language strings
    this.defaultLocale = {
      strings: {
          savingImages: 'Saving image to database...',
          savingFailed: 'Saving failed',
          savingSuccessful: 'Saving successful',
      },
    };

    // Initialize other properties
    this.formData     = new Object();
    this.filecounters = new Object();

    // Initialize sema
    const { Sema } = __webpack_require__(/*! async-sema */ "./node_modules/async-sema/lib/index.js");
    this.sema = new Sema(
      (this.opts.semaCalls || 1),
      {
        capacity: (this.opts.semaTokens  || 150) 
      }
    );

    // Initialize an object collecting the finished files
    // This object is observed during postprocessing (awaitSaveRequest)
    this.finishedFiles = _gullerya_object_observer__WEBPACK_IMPORTED_MODULE_2__.Observable.from({}, { async: true });

    this.i18nInit();
  }

  /**
   * Setter for options
   *
   * @param   {Object}   newOpts	  Options object
   *
   * @returns {Viod}
   */
  setOptions (newOpts) {
    super.setOptions(newOpts);
  }

  /**
   * Read out the uuid from the upload URL
   *
   * @param   {String}   uploadURL	   The URL
   *
   * @returns {String}   The uuid
   */
  resolveUuid (uploadURL) {
    let query = uploadURL.split('?')[1];
    let queryArray = query.split('&');

    for (let i = 0; i < queryArray.length; ++i) {
      if(queryArray[i].includes('uuid')) {
        return queryArray[i].replace('uuid=','');
      }
    }

    return '';
  }

  /**
   * Set an error for a specific file
   *
   * @param  {String}           error      Error message
   * @param  {object|Integer}   file       Object or ID of the uppy file
   * @param  {object}           response   Response object
   */
  setFileError (error, file, response) {
    let errorMsg = error || 'Unknown error';
    let fileID = (typeof file == 'number') ? file : file.id;

    // Add error to global uppy object
    this.uppy.setState({ error: errorMsg });

    // Add error to specific uppy file
    if (fileID in this.uppy.getState().files) {
      this.uppy.setFileState(fileID, {
        error: errorMsg,
        response: response,
      });
    }
  }

  /**
   * Set a success for a specific file
   *
   * @param  {object|Integer}   file       Object or ID of the uppy file
   * @param  {object}           response   Response object
   */
  setFileSuccess (file, response) {
    let fileID = (typeof file == 'number') ? file : file.id;

    // Add success to specific uppy file
    if (fileID in this.uppy.getState().files) {
      this.uppy.setFileState(fileID, {
        response: response,
      });
    }
  }

  /**
   * Add debug button to preview in dashboard
   *
   * @param  {Object}   file	    The Uppy file that was uploaded.
   * @param  {String}   type	    Button type. (success or danger)
   * @param  {String}   uuid	    The uppy upload id.
   * @param  {String}   style	    Class to add to the button. (optional)
   */
  addDebugBtn (file, type, uuid, style='') {
    // Create button element
    let btn = {
      'type' : type,
      'style' : style,
      'uuid' : uuid,
      'txt' : Joomla.JText._("COM_JOOMGALLERY_DEBUG_INFORMATION")
    }
    //let btn = '<button type="button" class="btn btn-'+type+' btn-sm '+style+'" data-bs-toggle="modal" data-bs-target="#modal'+file.uuid+'">'+Joomla.JText._("COM_JOOMGALLERY_DEBUG_INFORMATION")+'</button>';

    // Add element FileInfo
    this.uppy.setFileState(file.id, {debugBtn: btn});
  }

  /**
   * Add state text to preview in dashboard
   *
   * @param  {Object}   file	   The Uppy file that was uploaded.
   * @param  {String}   text	   The text content to be added.
   */
  addStateTxt (file, text) {
    // Add element FileInfo
    this.uppy.setFileState(file.id, {statetxt: text});
  }

  /**
   * Add a new title to an uploaded file
   *
   * @param  {Object}   file	   The Uppy file that was uploaded.
   * @param  {String}   title	   The title to be added.
   */
  addTitle (file, title) {
    let newMeta = file.meta;

    // Adjust meta
    if(file.meta && file.meta.name) {
      file.meta.name = title;
    }

    // Add element FileInfo
    this.uppy.setFileState(file.id, {
      name: title,
      meta: file.meta,
    });
  }

  /**
   * Create the HTML string for a bootstrap modal
   *
   * @param    {Object}   file        The Uppy file that was uploaded.
   * @param    {String}   uuid	      The uppy upload id.
   * @param    {Object}   response	  The response of the ajax request to save the file
   * 
   * @returns  {String}   The html string of the popup
   */
  createPopup (file, uuid, response) {
    // Create popup body
    let popupBody = '';

    if(Boolean(response.success) && Boolean(response.data) && Boolean(response.data.success)) {
      popupBody = Joomla.JText._('COM_JOOMGALLERY_SUCCESS_UPPY_UPLOAD').replace('{filename}', file.name);
    } else {
      popupBody = Joomla.JText._('COM_JOOMGALLERY_ERROR_UPPY_UPLOAD').replace('{filename}', file.name);
    }
    if(Boolean(response.message)) {
      popupBody = popupBody + '<br /><br />' + response.message;
    }
    if(Boolean(response.messages)) {
      if(Boolean(response.messages.notice)) {
        popupBody = popupBody + '<br /><br />' + response.messages.notice;
      }
      if(Boolean(response.messages.warning)) {
        popupBody = popupBody + '<br /><br />' + response.messages.warning;
      }
      if(Boolean(response.messages.error)) {
        popupBody = popupBody + '<br /><br />' + response.messages.error;
      }
    } 
    if(Boolean(response.data) && Boolean(response.data.error)) {
      popupBody = popupBody + '<br /><br />' + response.data.error;
    } 

    // Create popup
    let html =    '<div class="joomla-modal modal fade" id="modal'+uuid+'" tabindex="-1" aria-labelledby="modal'+uuid+'Label" aria-hidden="true">';
    html = html +   '<div class="modal-dialog modal-lg">';
    html = html +      '<div class="modal-content">';
    html = html +           '<div class="modal-header">';
    html = html +               '<h3 class="modal-title" id="modal'+uuid+'Label">'+Joomla.JText._('COM_JOOMGALLERY_DEBUG_INFORMATION')+'</h3>';
    html = html +               '<button type="button" class="btn-close novalidate" data-bs-dismiss="modal" aria-label="'+Joomla.JText._('JCLOSE')+'"></button>';
    html = html +           '</div>';
    html = html +           '<div class="modal-body">';
    html = html +               '<div id="'+uuid+'-ModalBody">'+popupBody+'</div>';
    html = html +           '</div>';
    html = html +           '<div class="modal-footer">';
    html = html +               '<button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="event.preventDefault()" aria-label="'+Joomla.JText._('JCLOSE')+'">'+Joomla.JText._('JCLOSE')+'</button>';
    html = html +           '</div>';
    html = html +      '</div>';
    html = html +   '</div>';
    html = html + '</div>';

    return html;
  }

  /**
   * Sends an asynchronous fetch request with form data from formID
   * for a corresponding file
   *
   * @param   {String}   tusID       ID of the tus connection (upload)
   * @param   {String}   fileID      ID of the corresponding file
   *
   * @returns {Object}   Result object 
   *                     {success: bool, status: int, message: string, messages: array, data: object}
   *                      data: response data created by JSON.parse
   */
  async sendFetchRequest (tusID, fileID) {
    await this.sema.acquire();

    // initialize variable
    let res = {error: 'Nothing sent...'};

    try {
      // Catch form and data
      let formData = this.formData;
      formData.append('jform[uuid]', tusID);
      formData.append('jform[filecounter]', this.filecounters[fileID]);
      if(formData.get('jform[imgtext]').trim().length === 0) {
        // Receive text content from editor
        let txt = Joomla.editors.instances['jform_imgtext'].getValue();
        formData.set('jform[imgtext]', txt);
      }
      let url = document.getElementById(this.formID).getAttribute('action');

      // Set request parameters
      let parameters = {
        method: 'POST',
        mode: 'same-origin',
        cache: 'default',
        redirect: 'follow',
        referrerPolicy: 'no-referrer-when-downgrade',
        body: formData,
      };

      // Perform the fetch request
      let response = await fetch(url, parameters);

      // Resolve promise as text string
      res = await response.text();

      if (!response.ok) {
        // Catch network error
        console.log(res);
        return {success: false, status: response.status, message: response.statusText, messages: {}, data: {error: res}};
      }

      if(res.startsWith('{"success"')) {
        // Response is of type json --> everything fine
        res = JSON.parse(res);
        res.status = response.status;
        res.data   = JSON.parse(res.data);
      } else {
        // Response is not of type json --> probably some php warnings/notices
        let split = res.split('\n{"');
        let temp  = JSON.parse('{"'+split[1]);
        let data  = JSON.parse(temp.data);
        res = {success: true, status: response.status, message: split[0], messages: temp.messages, data: data};
      }

    } finally {
      this.sema.release();
    }
  
    return res;
  }

  /**
   * Uppy-Event: on('upload')
   * Fired when the upload starts.
   * Docs: https://uppy.io/docs/uppy/#upload-1
   *
   * @param   {Object}   data    Data object consists of `id` with upload ID and `fileIDs` array
   */
  prepareProcess (data) {
    // with file IDs in current upload
    console.log('Starting upload '+data.id+' for files '+data.fileIDs);

    // Check and validate the form
    let form = document.getElementById(this.formID);

    // When upload starts, save the data of the form
    this.formData = new FormData(form);

    // Get numbering start value
    let nmb_start = 0;
    if(document.getElementById('jform_nmb_start')) {
      nmb_start = parseInt(document.getElementById('jform_nmb_start').value);
    }

    // Add class to file to apply styles during saving process
    for (let i = 0; i < data.fileIDs.length; i++) {
      // // Add text uploading to file element
      let file = this.uppy.getFile(data.fileIDs[i]);
      this.addStateTxt(file, Joomla.JText._('COM_JOOMGALLERY_UPLOADING')+'...');

      // Store the class property filecounter
      this.filecounters[data.fileIDs[i]] = nmb_start+i;
    };
  }

  /**
   * Uppy-Event: on('upload-success')
   * Fired each time a single upload is completed.
   * Docs: https://uppy.io/docs/uppy/#upload-success
   *
   * @param   {UppyFile} file        The Uppy file that was uploaded.
   * @param   {Object}   response    An object with response data from the remote endpoint.
   */
  startSaveRequest (file, response) {
    if(this.uploadID == '' || this.uploadID != this.resolveUuid(response.uploadURL)) {
      this.uploadID = this.resolveUuid(response.uploadURL);
    }

    // File upload was successful
    console.log('Upload of '+file.name+' successful.');

    // Update progress
    this.uppy.emit('postprocess-progress', file, {
      mode: 'indeterminate',
      message: this.i18n('savingImages'),
    });

    // Variable to store the save state
    let successful = false;

    // Save the uploaded file to the database
    this.sendFetchRequest(this.uploadID, file.id).then(response => {
      if(response.success == false)  {
        // Ajax request failed
        this.uppy.log('[PostProcessor] Ajax request for file '+file.name+' failed.', 'error');
        console.log('[PostProcessor] Ajax request for file '+file.name+' failed.');
        console.log(response.message);
        console.log(response.messages);
        this.setFileError(Joomla.JText._('COM_JOOMGALLERY_ERROR_UPPY_SAVE_RECORD').replace('{filename}', file.name), file, response);

        // Add text saving to file element
        this.addStateTxt(file, 'Saving failed');

        // Add Button to upload form
        this.addDebugBtn(file, 'danger', this.uploadID);
      }
      else  {
        // Ajax request successful
        if(!response.data.success)
        {
          // Save record failed
          this.uppy.log('[PostProcessor] Save record to database of file '+file.name+' failed.', 'error');
          console.log('[PostProcessor] Save record to database of file '+file.name+' failed.');
          console.log(response.data.error);
          this.setFileError(Joomla.JText._('COM_JOOMGALLERY_ERROR_UPPY_SAVE_RECORD').replace('{filename}', file.name), file, response);

          // Add text saving to file element
          this.addStateTxt(file, 'Saving failed');

          // Add Button to upload form
          this.addDebugBtn(file, 'danger', this.uploadID);
        }
        else
        {
          // Save record successful
          this.uppy.log('[PostProcessor] Save record to database of file '+file.name+' successful.');
          console.log('[PostProcessor] Save record to database of file '+file.name+' successful.');
          this.setFileSuccess(file, response);

          // Change save state
          successful = true;

          // Add text saving to file element
          this.addStateTxt(file, 'Saving successful');

          // Exchange title of the upload file
          this.addTitle(file, response.data.record.imgtitle);

          // Add Button to upload form
          if(this.formData.get('jform[debug]') == 1) {
            this.addDebugBtn(file, 'success', this.uploadID);
            console.log(response.data.record);
          }
        }
      }

      // Add file ID to the observed object of finished files
      this.finishedFiles[file.id] = {success: successful, file: file};
    });

    // Add debug popup
    if(!successful || (successful && this.formData.get('jform[debug]') == 1)) {
      let div       = document.createElement('div');
      div.innerHTML = this.createPopup(file, this.uploadID, response);
      document.getElementById('popup-area').appendChild(div);

      new bootstrap.Modal(document.getElementById('modal'+file.uuid));
    }
  }

  /**
   * Uppy-Event: on('upload-error')
   * Fired each time a single upload failed.
   * Docs: https://uppy.io/docs/uppy/#upload-error
   *
   * @param   {UppyFile} file        The Uppy file which didn’t upload.
   * @param   {Object}   error       The error object.
   * @param   {Object}   response    An object with response data from the remote endpoint.
   */
  handleError (file, error, response) {
    // file upload failed
    console.log('Upload of '+file.name+' failed.');

    // Add text saving to file element
    this.addStateTxt(file, 'Upload failed');

    // Add Button to upload form
    this.addDebugBtn(file, 'danger', this.uploadID);

    // Add Popup
    let temp_resp = {success: false};
    let div       = document.createElement('div');
    div.innerHTML = this.createPopup(file, this.uploadID, temp_resp);
    document.getElementById('popup-area').appendChild(div);

    new bootstrap.Modal(document.getElementById('modal'+file.uuid));
  }

  /**
   * Uppy-PostProcessor
   * Postprocessing function which is hooked into the uppy pipeline
   * Doc: https://uppy.io/docs/guides/building-plugins/#upload-hooks
   *
   * @param   {Array}    fileIDs     List of file IDs that are being uploaded
   * @param   {String}   uploadID    ID of the current upload 
   *
   * @returns {Promise}  Promise to signal completion
   */
  async awaitSaveRequest(fileIDs, uploadID) {
    //console.log('start observing...');

    const observeChanges = () => {
      return new Promise((resolve) => {
        let nmbFinished = 0;

        _gullerya_object_observer__WEBPACK_IMPORTED_MODULE_2__.Observable.observe(this.finishedFiles, changes => {
          // Executed every time something changes in this.finishedFiles
          nmbFinished++;

          let c = 0;
          changes.forEach(change => {
            if(c === 0) {
              let file = this.uppy.getFile(change.value.file.id);
              this.uppy.emit('postprocess-complete', file);

              //console.log('new observed finished file:');
              //console.log(change.value.file.id);
            }
            c++;
          });

          if(nmbFinished >= fileIDs.length) {
            // Resolve the Promise when all observed changes are processed
            resolve();
          }
        });

        // Completion of files which are completed before observation starts
        for (let key in this.finishedFiles) {
          nmbFinished++;

          let file = this.uppy.getFile(this.finishedFiles[key].file.id);
          this.uppy.emit('postprocess-complete', file);

          //console.log('already finished files:');
          //console.log(this.finishedFiles[key].file.id);
        }

        if(nmbFinished >= fileIDs.length) {
          // Resolve the Promise when all observed changes are processed
          resolve();
        }
      });
    };
    return observeChanges().then(() => {console.log('[PostProcessor] All records have been saved. End of PostProcessing.');});
  }

  /**
   * Installation of the plugin
   * Doc: https://uppy.io/docs/guides/building-plugins/#install
   *
   * @returns {Void}
   */
  install () {
    this.uppy.addPostProcessor(this.awaitSaveRequest);

    this.uppy.on('upload', this.prepareProcess);
    this.uppy.on('upload-success', this.startSaveRequest);
    this.uppy.on('upload-error', this.handleError);
  }

  /**
   * Deinstallation of the plugin
   * Doc: https://uppy.io/docs/guides/building-plugins/#uninstall
   *
   * @returns {Void}
   */
  uninstall () {
    this.uppy.removePostProcessor(this.awaitSaveRequest);
  }
}

/***/ }),

/***/ "./node_modules/@gullerya/object-observer/dist/object-observer.min.js":
/*!****************************************************************************!*\
  !*** ./node_modules/@gullerya/object-observer/dist/object-observer.min.js ***!
  \****************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ObjectObserver: () => (/* binding */ k),
/* harmony export */   Observable: () => (/* binding */ v)
/* harmony export */ });
const m="insert",x="update",E="delete",T="reverse",K="shuffle",c=Symbol.for("object-observer-meta-key-0"),$={async:1},N=o=>{if(!o||typeof o!="object")return null;const t={},e=[];for(const[r,n]of Object.entries(o))if(r==="path"){if(typeof n!="string"||n==="")throw new Error('"path" option, if/when provided, MUST be a non-empty string');t[r]=n}else if(r==="pathsOf"){if(o.path)throw new Error('"pathsOf" option MAY NOT be specified together with "path" option');if(typeof n!="string")throw new Error('"pathsOf" option, if/when provided, MUST be a string (MAY be empty)');t[r]=o.pathsOf.split(".").filter(Boolean)}else if(r==="pathsFrom"){if(o.path||o.pathsOf)throw new Error('"pathsFrom" option MAY NOT be specified together with "path"/"pathsOf" option/s');if(typeof n!="string"||n==="")throw new Error('"pathsFrom" option, if/when provided, MUST be a non-empty string');t[r]=n}else e.push(r);if(e.length)throw new Error(`'${e.join(", ")}' is/are not a valid observer option/s`);return t},Y=(o,t,e)=>{const r={};r[c]=t;for(const n in o)r[n]=g(o[n],n,t,e);return r},I=(o,t,e)=>{let r=o.length;const n=new Array(r);n[c]=t;for(let i=0;i<r;i++)n[i]=g(o[i],i,t,e);return n},B=(o,t)=>(o[c]=t,o),D=(o,t)=>{if(o===null)return t;let e=t;if(o.path){const r=o.path;e=t.filter(n=>n.path.join(".")===r)}else if(o.pathsOf){const r=o.pathsOf,n=r.join(".");e=t.filter(i=>(i.path.length===r.length+1||i.path.length===r.length&&(i.type===T||i.type===K))&&i.path.join(".").startsWith(n))}else if(o.pathsFrom){const r=o.pathsFrom;e=t.filter(n=>n.path.join(".").startsWith(r))}return e},R=(o,t)=>{try{o(t)}catch(e){console.error(`failed to notify listener ${o} with ${t}`,e)}},z=function(){const t=this.batches;this.batches=[];for(const[e,r]of t)R(e,r)},y=(o,t)=>{let e=o,r,n,i,l,h,s;const u=t.length;do{for(r=e.options.async,n=e.observers,s=n.length;s--;)if([i,l]=n[s],h=D(l,t),h.length)if(r){e.batches.length===0&&queueMicrotask(z.bind(e));let a;for(const p of e.batches)if(p[0]===i){a=p;break}a||(a=[i,[]],e.batches.push(a)),Array.prototype.push.apply(a[1],h)}else R(i,h);const f=e.parent;if(f){for(let a=0;a<u;a++){const p=t[a];t[a]=new b(p.type,[e.ownKey,...p.path],p.value,p.oldValue,p.object)}e=f}else e=null}while(e)},g=(o,t,e,r)=>r!==void 0&&r.has(o)?null:typeof o!="object"||o===null?o:Array.isArray(o)?new U({target:o,ownKey:t,parent:e,visited:r}).proxy:ArrayBuffer.isView(o)?new W({target:o,ownKey:t,parent:e}).proxy:o instanceof Date?o:new V({target:o,ownKey:t,parent:e,visited:r}).proxy,q=function(){const t=this[c],e=t.target,r=e.length-1;let n=e.pop();if(n&&typeof n=="object"){const l=n[c];l&&(n=l.detach())}const i=[new b(E,[r],void 0,n,this)];return y(t,i),n},H=function(){const t=this[c],e=t.target,r=arguments.length,n=new Array(r),i=e.length;for(let s=0;s<r;s++)n[s]=g(arguments[s],i+s,t);const l=Reflect.apply(e.push,e,n),h=[];for(let s=i,u=e.length;s<u;s++)h[s-i]=new b(m,[s],e[s],void 0,this);return y(t,h),l},G=function(){const t=this[c],e=t.target;let r,n,i,l,h;for(r=e.shift(),r&&typeof r=="object"&&(h=r[c],h&&(r=h.detach())),n=0,i=e.length;n<i;n++)l=e[n],l&&typeof l=="object"&&(h=l[c],h&&(h.ownKey=n));const s=[new b(E,[0],void 0,r,this)];return y(t,s),r},J=function(){const t=this[c],e=t.target,r=arguments.length,n=new Array(r);for(let s=0;s<r;s++)n[s]=g(arguments[s],s,t);const i=Reflect.apply(e.unshift,e,n);for(let s=0,u=e.length,f;s<u;s++)if(f=e[s],f&&typeof f=="object"){const a=f[c];a&&(a.ownKey=s)}const l=n.length,h=new Array(l);for(let s=0;s<l;s++)h[s]=new b(m,[s],e[s],void 0,this);return y(t,h),i},F=function(){const t=this[c],e=t.target;let r,n,i;for(e.reverse(),r=0,n=e.length;r<n;r++)if(i=e[r],i&&typeof i=="object"){const h=i[c];h&&(h.ownKey=r)}const l=[new b(T,[],void 0,void 0,this)];return y(t,l),this},P=function(t){const e=this[c],r=e.target;let n,i,l;for(r.sort(t),n=0,i=r.length;n<i;n++)if(l=r[n],l&&typeof l=="object"){const s=l[c];s&&(s.ownKey=n)}const h=[new b(K,[],void 0,void 0,this)];return y(e,h),this},L=function(t,e,r){const n=this[c],i=n.target,l=[],h=i.length,s=i.slice(0);if(e=e===void 0?0:e<0?Math.max(h+e,0):Math.min(e,h),r=r===void 0?h:r<0?Math.max(h+r,0):Math.min(r,h),e<h&&r>e){i.fill(t,e,r);let u;for(let f=e,a,p;f<r;f++)a=i[f],i[f]=g(a,f,n),f in s?(p=s[f],p&&typeof p=="object"&&(u=p[c],u&&(p=u.detach())),l.push(new b(x,[f],i[f],p,this))):l.push(new b(m,[f],i[f],void 0,this));y(n,l)}return this},C=function(t,e,r){const n=this[c],i=n.target,l=i.length;t=t<0?Math.max(l+t,0):t,e=e===void 0?0:e<0?Math.max(l+e,0):Math.min(e,l),r=r===void 0?l:r<0?Math.max(l+r,0):Math.min(r,l);const h=Math.min(r-e,l-t);if(t<l&&t!==e&&h>0){const s=i.slice(0),u=[];i.copyWithin(t,e,r);for(let f=t,a,p,O;f<t+h;f++)a=i[f],a&&typeof a=="object"&&(a=g(a,f,n),i[f]=a),p=s[f],p&&typeof p=="object"&&(O=p[c],O&&(p=O.detach())),!(typeof a!="object"&&a===p)&&u.push(new b(x,[f],a,p,this));y(n,u)}return this},Q=function(){const t=this[c],e=t.target,r=arguments.length,n=new Array(r),i=e.length;for(let w=0;w<r;w++)n[w]=g(arguments[w],w,t);const l=r===0?0:n[0]<0?i+n[0]:n[0],h=r<2?i-l:n[1],s=Math.max(r-2,0),u=Reflect.apply(e.splice,e,n),f=e.length;let a;for(let w=0,A;w<f;w++)A=e[w],A&&typeof A=="object"&&(a=A[c],a&&(a.ownKey=w));let p,O,j;for(p=0,O=u.length;p<O;p++)j=u[p],j&&typeof j=="object"&&(a=j[c],a&&(u[p]=a.detach()));const M=[];let d;for(d=0;d<h;d++)d<s?M.push(new b(x,[l+d],e[l+d],u[d],this)):M.push(new b(E,[l+d],void 0,u[d],this));for(;d<s;d++)M.push(new b(m,[l+d],e[l+d],void 0,this));return y(t,M),u},X=function(t,e){const r=this[c],n=r.target,i=t.length,l=n.slice(0);e=e||0,n.set(t,e);const h=new Array(i);for(let s=e;s<i+e;s++)h[s-e]=new b(x,[s],n[s],l[s],this);y(r,h)},Z={pop:q,push:H,shift:G,unshift:J,reverse:F,sort:P,fill:L,copyWithin:C,splice:Q},_={reverse:F,sort:P,fill:L,copyWithin:C,set:X};class b{constructor(t,e,r,n,i){this.type=t,this.path=e,this.value=r,this.oldValue=n,this.object=i}}class S{constructor(t,e){const{target:r,parent:n,ownKey:i,visited:l=new Set}=t;n&&i!==void 0?(this.parent=n,this.ownKey=i):(this.parent=null,this.ownKey=null),l.add(r);const h=e(r,this,l);l.delete(r),this.observers=[],this.revocable=Proxy.revocable(h,this),this.proxy=this.revocable.proxy,this.target=h,this.options=this.processOptions(t.options),this.options.async&&(this.batches=[])}processOptions(t){if(t){if(typeof t!="object")throw new Error(`Observable options if/when provided, MAY only be an object, got '${t}'`);const e=Object.keys(t).filter(r=>!(r in $));if(e.length)throw new Error(`'${e.join(", ")}' is/are not a valid Observable option/s`);return Object.assign({},t)}else return{}}detach(){return this.parent=null,this.target}set(t,e,r){let n=t[e];if(r!==n){const i=g(r,e,this);if(t[e]=i,n&&typeof n=="object"){const h=n[c];h&&(n=h.detach())}const l=n===void 0?[new b(m,[e],i,void 0,this.proxy)]:[new b(x,[e],i,n,this.proxy)];y(this,l)}return!0}deleteProperty(t,e){let r=t[e];if(delete t[e],r&&typeof r=="object"){const i=r[c];i&&(r=i.detach())}const n=[new b(E,[e],void 0,r,this.proxy)];return y(this,n),!0}}class V extends S{constructor(t){super(t,Y)}}class U extends S{constructor(t){super(t,I)}get(t,e){return Z[e]||t[e]}}class W extends S{constructor(t){super(t,B)}get(t,e){return _[e]||t[e]}}const v=Object.freeze({from:(o,t)=>{if(!o||typeof o!="object")throw new Error("observable MAY ONLY be created from a non-null object");if(o[c])return o;if(Array.isArray(o))return new U({target:o,ownKey:null,parent:null,options:t}).proxy;if(ArrayBuffer.isView(o))return new W({target:o,ownKey:null,parent:null,options:t}).proxy;if(o instanceof Date)throw new Error(`${o} found to be one of a non-observable types`);return new V({target:o,ownKey:null,parent:null,options:t}).proxy},isObservable:o=>!!(o&&o[c]),observe:(o,t,e)=>{if(!v.isObservable(o))throw new Error("invalid observable parameter");if(typeof t!="function")throw new Error(`observer MUST be a function, got '${t}'`);const r=o[c].observers;r.some(n=>n[0]===t)?console.warn("observer may be bound to an observable only once; will NOT rebind"):r.push([t,N(e)])},unobserve:(o,...t)=>{if(!v.isObservable(o))throw new Error("invalid observable parameter");const e=o[c].observers;let r=e.length;if(r){if(!t.length){e.splice(0);return}for(;r;)t.indexOf(e[--r][0])>=0&&e.splice(r,1)}}});class k{#t;#e;constructor(t){this.#t=t,this.#e=new Set,Object.freeze(this)}observe(t,e){const r=v.from(t);return v.observe(r,this.#t,e),this.#e.add(r),r}unobserve(t){v.unobserve(t,this.#t),this.#e.delete(t)}disconnect(){for(const t of this.#e)v.unobserve(t,this.#t);this.#e.clear()}}
//# sourceMappingURL=object-observer.min.js.map


/***/ }),

/***/ "./node_modules/@uppy/companion-client/lib/AuthError.js":
/*!**************************************************************!*\
  !*** ./node_modules/@uppy/companion-client/lib/AuthError.js ***!
  \**************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });


class AuthError extends Error {
  constructor() {
    super('Authorization required');
    this.name = 'AuthError';
    this.isAuthError = true;
  }

}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (AuthError);

/***/ }),

/***/ "./node_modules/@uppy/companion-client/lib/Provider.js":
/*!*************************************************************!*\
  !*** ./node_modules/@uppy/companion-client/lib/Provider.js ***!
  \*************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Provider)
/* harmony export */ });
/* harmony import */ var _RequestClient_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./RequestClient.js */ "./node_modules/@uppy/companion-client/lib/RequestClient.js");
/* harmony import */ var _tokenStorage_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./tokenStorage.js */ "./node_modules/@uppy/companion-client/lib/tokenStorage.js");





const getName = id => {
  return id.split('-').map(s => s.charAt(0).toUpperCase() + s.slice(1)).join(' ');
};

class Provider extends _RequestClient_js__WEBPACK_IMPORTED_MODULE_0__["default"] {
  constructor(uppy, opts) {
    super(uppy, opts);
    this.provider = opts.provider;
    this.id = this.provider;
    this.name = this.opts.name || getName(this.id);
    this.pluginId = this.opts.pluginId;
    this.tokenKey = `companion-${this.pluginId}-auth-token`;
    this.companionKeysParams = this.opts.companionKeysParams;
    this.preAuthToken = null;
  }

  async headers() {
    const [headers, token] = await Promise.all([super.headers(), this.getAuthToken()]);
    const authHeaders = {};

    if (token) {
      authHeaders['uppy-auth-token'] = token;
    }

    if (this.companionKeysParams) {
      authHeaders['uppy-credentials-params'] = btoa(JSON.stringify({
        params: this.companionKeysParams
      }));
    }

    return { ...headers,
      ...authHeaders
    };
  }

  onReceiveResponse(response) {
    super.onReceiveResponse(response);
    const plugin = this.uppy.getPlugin(this.pluginId);
    const oldAuthenticated = plugin.getPluginState().authenticated;
    const authenticated = oldAuthenticated ? response.status !== 401 : response.status < 400;
    plugin.setPluginState({
      authenticated
    });
    return response;
  }

  setAuthToken(token) {
    return this.uppy.getPlugin(this.pluginId).storage.setItem(this.tokenKey, token);
  }

  getAuthToken() {
    return this.uppy.getPlugin(this.pluginId).storage.getItem(this.tokenKey);
  }
  /**
   * Ensure we have a preauth token if necessary. Attempts to fetch one if we don't,
   * or rejects if loading one fails.
   */


  async ensurePreAuth() {
    if (this.companionKeysParams && !this.preAuthToken) {
      await this.fetchPreAuthToken();

      if (!this.preAuthToken) {
        throw new Error('Could not load authentication data required for third-party login. Please try again later.');
      }
    }
  }

  authUrl(queries) {
    if (queries === void 0) {
      queries = {};
    }

    const params = new URLSearchParams(queries);

    if (this.preAuthToken) {
      params.set('uppyPreAuthToken', this.preAuthToken);
    }

    return `${this.hostname}/${this.id}/connect?${params}`;
  }

  fileUrl(id) {
    return `${this.hostname}/${this.id}/get/${id}`;
  }

  async fetchPreAuthToken() {
    if (!this.companionKeysParams) {
      return;
    }

    try {
      const res = await this.post(`${this.id}/preauth/`, {
        params: this.companionKeysParams
      });
      this.preAuthToken = res.token;
    } catch (err) {
      this.uppy.log(`[CompanionClient] unable to fetch preAuthToken ${err}`, 'warning');
    }
  }

  list(directory) {
    return this.get(`${this.id}/list/${directory || ''}`);
  }

  logout() {
    return this.get(`${this.id}/logout`).then(response => Promise.all([response, this.uppy.getPlugin(this.pluginId).storage.removeItem(this.tokenKey)])).then(_ref => {
      let [response] = _ref;
      return response;
    });
  }

  static initPlugin(plugin, opts, defaultOpts) {
    /* eslint-disable no-param-reassign */
    plugin.type = 'acquirer';
    plugin.files = [];

    if (defaultOpts) {
      plugin.opts = { ...defaultOpts,
        ...opts
      };
    }

    if (opts.serverUrl || opts.serverPattern) {
      throw new Error('`serverUrl` and `serverPattern` have been renamed to `companionUrl` and `companionAllowedHosts` respectively in the 0.30.5 release. Please consult the docs (for example, https://uppy.io/docs/instagram/ for the Instagram plugin) and use the updated options.`');
    }

    if (opts.companionAllowedHosts) {
      const pattern = opts.companionAllowedHosts; // validate companionAllowedHosts param

      if (typeof pattern !== 'string' && !Array.isArray(pattern) && !(pattern instanceof RegExp)) {
        throw new TypeError(`${plugin.id}: the option "companionAllowedHosts" must be one of string, Array, RegExp`);
      }

      plugin.opts.companionAllowedHosts = pattern;
    } else if (/^(?!https?:\/\/).*$/i.test(opts.companionUrl)) {
      // does not start with https://
      plugin.opts.companionAllowedHosts = `https://${opts.companionUrl.replace(/^\/\//, '')}`;
    } else {
      plugin.opts.companionAllowedHosts = new URL(opts.companionUrl).origin;
    }

    plugin.storage = plugin.opts.storage || _tokenStorage_js__WEBPACK_IMPORTED_MODULE_1__;
    /* eslint-enable no-param-reassign */
  }

}

/***/ }),

/***/ "./node_modules/@uppy/companion-client/lib/RequestClient.js":
/*!******************************************************************!*\
  !*** ./node_modules/@uppy/companion-client/lib/RequestClient.js ***!
  \******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ RequestClient)
/* harmony export */ });
/* harmony import */ var _uppy_utils_lib_fetchWithNetworkError__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @uppy/utils/lib/fetchWithNetworkError */ "./node_modules/@uppy/utils/lib/fetchWithNetworkError.js");
/* harmony import */ var _uppy_utils_lib_ErrorWithCause__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @uppy/utils/lib/ErrorWithCause */ "./node_modules/@uppy/utils/lib/ErrorWithCause.js");
/* harmony import */ var _AuthError_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AuthError.js */ "./node_modules/@uppy/companion-client/lib/AuthError.js");


let _Symbol$for;

function _classPrivateFieldLooseBase(receiver, privateKey) { if (!Object.prototype.hasOwnProperty.call(receiver, privateKey)) { throw new TypeError("attempted to use private field on non-instance"); } return receiver; }

var id = 0;

function _classPrivateFieldLooseKey(name) { return "__private_" + id++ + "_" + name; }




const packageJson = {
  "version": "3.1.3"
}; // Remove the trailing slash so we can always safely append /xyz.

function stripSlash(url) {
  return url.replace(/\/$/, '');
}

async function handleJSONResponse(res) {
  if (res.status === 401) {
    throw new _AuthError_js__WEBPACK_IMPORTED_MODULE_2__["default"]();
  }

  const jsonPromise = res.json();

  if (res.ok) {
    return jsonPromise;
  }

  let errMsg = `Failed request with status: ${res.status}. ${res.statusText}`;

  try {
    const errData = await jsonPromise;
    errMsg = errData.message ? `${errMsg} message: ${errData.message}` : errMsg;
    errMsg = errData.requestId ? `${errMsg} request-Id: ${errData.requestId}` : errMsg;
  } catch {
    /* if the response contains invalid JSON, let's ignore the error */
  }

  throw new Error(errMsg);
} // todo pull out into core instead?


const allowedHeadersCache = new Map();

var _companionHeaders = /*#__PURE__*/_classPrivateFieldLooseKey("companionHeaders");

var _getUrl = /*#__PURE__*/_classPrivateFieldLooseKey("getUrl");

var _request = /*#__PURE__*/_classPrivateFieldLooseKey("request");

_Symbol$for = Symbol.for('uppy test: getCompanionHeaders');
class RequestClient {
  constructor(uppy, opts) {
    Object.defineProperty(this, _request, {
      value: _request2
    });
    Object.defineProperty(this, _getUrl, {
      value: _getUrl2
    });
    Object.defineProperty(this, _companionHeaders, {
      writable: true,
      value: void 0
    });
    this.uppy = uppy;
    this.opts = opts;
    this.onReceiveResponse = this.onReceiveResponse.bind(this);
    _classPrivateFieldLooseBase(this, _companionHeaders)[_companionHeaders] = opts == null ? void 0 : opts.companionHeaders;
  }

  setCompanionHeaders(headers) {
    _classPrivateFieldLooseBase(this, _companionHeaders)[_companionHeaders] = headers;
  }

  [_Symbol$for]() {
    return _classPrivateFieldLooseBase(this, _companionHeaders)[_companionHeaders];
  }

  get hostname() {
    const {
      companion
    } = this.uppy.getState();
    const host = this.opts.companionUrl;
    return stripSlash(companion && companion[host] ? companion[host] : host);
  }

  async headers() {
    const defaultHeaders = {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'Uppy-Versions': `@uppy/companion-client=${RequestClient.VERSION}`
    };
    return { ...defaultHeaders,
      ..._classPrivateFieldLooseBase(this, _companionHeaders)[_companionHeaders]
    };
  }

  onReceiveResponse(_ref) {
    let {
      headers
    } = _ref;
    const state = this.uppy.getState();
    const companion = state.companion || {};
    const host = this.opts.companionUrl; // Store the self-identified domain name for the Companion instance we just hit.

    if (headers.has('i-am') && headers.get('i-am') !== companion[host]) {
      this.uppy.setState({
        companion: { ...companion,
          [host]: headers.get('i-am')
        }
      });
    }
  }

  /*
    Preflight was added to avoid breaking change between older Companion-client versions and
    newer Companion versions and vice-versa. Usually the break will manifest via CORS errors because a
    version of companion-client could be sending certain headers to a version of Companion server that
    does not support those headers. In which case, the default preflight would lead to CORS.
    So to avoid those errors, we do preflight ourselves, to see what headers the Companion server
    we are communicating with allows. And based on that, companion-client knows what headers to
    send and what headers to not send.
     The preflight only happens once throughout the life-cycle of a certain
    Companion-client <-> Companion-server pair (allowedHeadersCache).
    Subsequent requests use the cached result of the preflight.
    However if there is an error retrieving the allowed headers, we will try again next time
  */
  async preflight(path) {
    const allowedHeadersCached = allowedHeadersCache.get(this.hostname);
    if (allowedHeadersCached != null) return allowedHeadersCached;
    const fallbackAllowedHeaders = ['accept', 'content-type', 'uppy-auth-token'];

    const promise = (async () => {
      try {
        const response = await fetch(_classPrivateFieldLooseBase(this, _getUrl)[_getUrl](path), {
          method: 'OPTIONS'
        });
        const header = response.headers.get('access-control-allow-headers');

        if (header == null || header === '*') {
          allowedHeadersCache.set(this.hostname, fallbackAllowedHeaders);
          return fallbackAllowedHeaders;
        }

        this.uppy.log(`[CompanionClient] adding allowed preflight headers to companion cache: ${this.hostname} ${header}`);
        const allowedHeaders = header.split(',').map(headerName => headerName.trim().toLowerCase());
        allowedHeadersCache.set(this.hostname, allowedHeaders);
        return allowedHeaders;
      } catch (err) {
        this.uppy.log(`[CompanionClient] unable to make preflight request ${err}`, 'warning'); // If the user gets a network error or similar, we should try preflight
        // again next time, or else we might get incorrect behaviour.

        allowedHeadersCache.delete(this.hostname); // re-fetch next time

        return fallbackAllowedHeaders;
      }
    })();

    allowedHeadersCache.set(this.hostname, promise);
    return promise;
  }

  async preflightAndHeaders(path) {
    const [allowedHeaders, headers] = await Promise.all([this.preflight(path), this.headers()]); // filter to keep only allowed Headers

    return Object.fromEntries(Object.entries(headers).filter(_ref2 => {
      let [header] = _ref2;

      if (!allowedHeaders.includes(header.toLowerCase())) {
        this.uppy.log(`[CompanionClient] excluding disallowed header ${header}`);
        return false;
      }

      return true;
    }));
  }

  async get(path, options) {
    if (options === void 0) {
      options = undefined;
    }

    // TODO: remove boolean support for options that was added for backward compatibility.
    // eslint-disable-next-line no-param-reassign
    if (typeof options === 'boolean') options = {
      skipPostResponse: options
    };
    return _classPrivateFieldLooseBase(this, _request)[_request]({ ...options,
      path
    });
  }

  async post(path, data, options) {
    if (options === void 0) {
      options = undefined;
    }

    // TODO: remove boolean support for options that was added for backward compatibility.
    // eslint-disable-next-line no-param-reassign
    if (typeof options === 'boolean') options = {
      skipPostResponse: options
    };
    return _classPrivateFieldLooseBase(this, _request)[_request]({ ...options,
      path,
      method: 'POST',
      data
    });
  }

  async delete(path, data, options) {
    if (data === void 0) {
      data = undefined;
    }

    // TODO: remove boolean support for options that was added for backward compatibility.
    // eslint-disable-next-line no-param-reassign
    if (typeof options === 'boolean') options = {
      skipPostResponse: options
    };
    return _classPrivateFieldLooseBase(this, _request)[_request]({ ...options,
      path,
      method: 'DELETE',
      data
    });
  }

}

function _getUrl2(url) {
  if (/^(https?:|)\/\//.test(url)) {
    return url;
  }

  return `${this.hostname}/${url}`;
}

async function _request2(_ref3) {
  let {
    path,
    method = 'GET',
    data,
    skipPostResponse,
    signal
  } = _ref3;

  try {
    const headers = await this.preflightAndHeaders(path);
    const response = await (0,_uppy_utils_lib_fetchWithNetworkError__WEBPACK_IMPORTED_MODULE_0__["default"])(_classPrivateFieldLooseBase(this, _getUrl)[_getUrl](path), {
      method,
      signal,
      headers,
      credentials: this.opts.companionCookiesRule || 'same-origin',
      body: data ? JSON.stringify(data) : null
    });
    if (!skipPostResponse) this.onReceiveResponse(response);
    return handleJSONResponse(response);
  } catch (err) {
    if (err != null && err.isAuthError) throw err;
    throw new _uppy_utils_lib_ErrorWithCause__WEBPACK_IMPORTED_MODULE_1__["default"](`Could not ${method} ${_classPrivateFieldLooseBase(this, _getUrl)[_getUrl](path)}`, {
      cause: err
    });
  }
}

RequestClient.VERSION = packageJson.version;

/***/ }),

/***/ "./node_modules/@uppy/companion-client/lib/SearchProvider.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@uppy/companion-client/lib/SearchProvider.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ SearchProvider)
/* harmony export */ });
/* harmony import */ var _RequestClient_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./RequestClient.js */ "./node_modules/@uppy/companion-client/lib/RequestClient.js");




const getName = id => {
  return id.split('-').map(s => s.charAt(0).toUpperCase() + s.slice(1)).join(' ');
};

class SearchProvider extends _RequestClient_js__WEBPACK_IMPORTED_MODULE_0__["default"] {
  constructor(uppy, opts) {
    super(uppy, opts);
    this.provider = opts.provider;
    this.id = this.provider;
    this.name = this.opts.name || getName(this.id);
    this.pluginId = this.opts.pluginId;
  }

  fileUrl(id) {
    return `${this.hostname}/search/${this.id}/get/${id}`;
  }

  search(text, queries) {
    return this.get(`search/${this.id}/list?q=${encodeURIComponent(text)}${queries ? `&${queries}` : ''}`);
  }

}

/***/ }),

/***/ "./node_modules/@uppy/companion-client/lib/Socket.js":
/*!***********************************************************!*\
  !*** ./node_modules/@uppy/companion-client/lib/Socket.js ***!
  \***********************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ UppySocket)
/* harmony export */ });
/* harmony import */ var namespace_emitter__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! namespace-emitter */ "./node_modules/namespace-emitter/index.js");
let _Symbol$for, _Symbol$for2;

function _classPrivateFieldLooseBase(receiver, privateKey) { if (!Object.prototype.hasOwnProperty.call(receiver, privateKey)) { throw new TypeError("attempted to use private field on non-instance"); } return receiver; }

var id = 0;

function _classPrivateFieldLooseKey(name) { return "__private_" + id++ + "_" + name; }



var _queued = /*#__PURE__*/_classPrivateFieldLooseKey("queued");

var _emitter = /*#__PURE__*/_classPrivateFieldLooseKey("emitter");

var _isOpen = /*#__PURE__*/_classPrivateFieldLooseKey("isOpen");

var _socket = /*#__PURE__*/_classPrivateFieldLooseKey("socket");

var _handleMessage = /*#__PURE__*/_classPrivateFieldLooseKey("handleMessage");

_Symbol$for = Symbol.for('uppy test: getSocket');
_Symbol$for2 = Symbol.for('uppy test: getQueued');
class UppySocket {
  constructor(opts) {
    Object.defineProperty(this, _queued, {
      writable: true,
      value: []
    });
    Object.defineProperty(this, _emitter, {
      writable: true,
      value: namespace_emitter__WEBPACK_IMPORTED_MODULE_0__()
    });
    Object.defineProperty(this, _isOpen, {
      writable: true,
      value: false
    });
    Object.defineProperty(this, _socket, {
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, _handleMessage, {
      writable: true,
      value: e => {
        try {
          const message = JSON.parse(e.data);
          this.emit(message.action, message.payload);
        } catch (err) {
          // TODO: use a more robust error handler.
          console.log(err); // eslint-disable-line no-console
        }
      }
    });
    this.opts = opts;

    if (!opts || opts.autoOpen !== false) {
      this.open();
    }
  }

  get isOpen() {
    return _classPrivateFieldLooseBase(this, _isOpen)[_isOpen];
  }

  [_Symbol$for]() {
    return _classPrivateFieldLooseBase(this, _socket)[_socket];
  }

  [_Symbol$for2]() {
    return _classPrivateFieldLooseBase(this, _queued)[_queued];
  }

  open() {
    if (_classPrivateFieldLooseBase(this, _socket)[_socket] != null) return;
    _classPrivateFieldLooseBase(this, _socket)[_socket] = new WebSocket(this.opts.target);

    _classPrivateFieldLooseBase(this, _socket)[_socket].onopen = () => {
      _classPrivateFieldLooseBase(this, _isOpen)[_isOpen] = true;

      while (_classPrivateFieldLooseBase(this, _queued)[_queued].length > 0 && _classPrivateFieldLooseBase(this, _isOpen)[_isOpen]) {
        const first = _classPrivateFieldLooseBase(this, _queued)[_queued].shift();

        this.send(first.action, first.payload);
      }
    };

    _classPrivateFieldLooseBase(this, _socket)[_socket].onclose = () => {
      _classPrivateFieldLooseBase(this, _isOpen)[_isOpen] = false;
      _classPrivateFieldLooseBase(this, _socket)[_socket] = null;
    };

    _classPrivateFieldLooseBase(this, _socket)[_socket].onmessage = _classPrivateFieldLooseBase(this, _handleMessage)[_handleMessage];
  }

  close() {
    var _classPrivateFieldLoo;

    (_classPrivateFieldLoo = _classPrivateFieldLooseBase(this, _socket)[_socket]) == null ? void 0 : _classPrivateFieldLoo.close();
  }

  send(action, payload) {
    // attach uuid
    if (!_classPrivateFieldLooseBase(this, _isOpen)[_isOpen]) {
      _classPrivateFieldLooseBase(this, _queued)[_queued].push({
        action,
        payload
      });

      return;
    }

    _classPrivateFieldLooseBase(this, _socket)[_socket].send(JSON.stringify({
      action,
      payload
    }));
  }

  on(action, handler) {
    _classPrivateFieldLooseBase(this, _emitter)[_emitter].on(action, handler);
  }

  emit(action, payload) {
    _classPrivateFieldLooseBase(this, _emitter)[_emitter].emit(action, payload);
  }

  once(action, handler) {
    _classPrivateFieldLooseBase(this, _emitter)[_emitter].once(action, handler);
  }

}

/***/ }),

/***/ "./node_modules/@uppy/companion-client/lib/index.js":
/*!**********************************************************!*\
  !*** ./node_modules/@uppy/companion-client/lib/index.js ***!
  \**********************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Provider: () => (/* reexport safe */ _Provider_js__WEBPACK_IMPORTED_MODULE_1__["default"]),
/* harmony export */   RequestClient: () => (/* reexport safe */ _RequestClient_js__WEBPACK_IMPORTED_MODULE_0__["default"]),
/* harmony export */   SearchProvider: () => (/* reexport safe */ _SearchProvider_js__WEBPACK_IMPORTED_MODULE_2__["default"]),
/* harmony export */   Socket: () => (/* reexport safe */ _Socket_js__WEBPACK_IMPORTED_MODULE_3__["default"])
/* harmony export */ });
/* harmony import */ var _RequestClient_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./RequestClient.js */ "./node_modules/@uppy/companion-client/lib/RequestClient.js");
/* harmony import */ var _Provider_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Provider.js */ "./node_modules/@uppy/companion-client/lib/Provider.js");
/* harmony import */ var _SearchProvider_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SearchProvider.js */ "./node_modules/@uppy/companion-client/lib/SearchProvider.js");
/* harmony import */ var _Socket_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Socket.js */ "./node_modules/@uppy/companion-client/lib/Socket.js");

/**
 * Manages communications with Companion
 */






/***/ }),

/***/ "./node_modules/@uppy/companion-client/lib/tokenStorage.js":
/*!*****************************************************************!*\
  !*** ./node_modules/@uppy/companion-client/lib/tokenStorage.js ***!
  \*****************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getItem: () => (/* binding */ getItem),
/* harmony export */   removeItem: () => (/* binding */ removeItem),
/* harmony export */   setItem: () => (/* binding */ setItem)
/* harmony export */ });

/**
 * This module serves as an Async wrapper for LocalStorage
 */

function setItem(key, value) {
  return new Promise(resolve => {
    localStorage.setItem(key, value);
    resolve();
  });
}
function getItem(key) {
  return Promise.resolve(localStorage.getItem(key));
}
function removeItem(key) {
  return new Promise(resolve => {
    localStorage.removeItem(key);
    resolve();
  });
}

/***/ }),

/***/ "./node_modules/@uppy/core/lib/BasePlugin.js":
/*!***************************************************!*\
  !*** ./node_modules/@uppy/core/lib/BasePlugin.js ***!
  \***************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ BasePlugin)
/* harmony export */ });
/* harmony import */ var _uppy_utils_lib_Translator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @uppy/utils/lib/Translator */ "./node_modules/@uppy/utils/lib/Translator.js");
/**
 * Core plugin logic that all plugins share.
 *
 * BasePlugin does not contain DOM rendering so it can be used for plugins
 * without a user interface.
 *
 * See `Plugin` for the extended version with Preact rendering for interfaces.
 */

class BasePlugin {
  constructor(uppy, opts) {
    if (opts === void 0) {
      opts = {};
    }

    this.uppy = uppy;
    this.opts = opts;
  }

  getPluginState() {
    const {
      plugins
    } = this.uppy.getState();
    return plugins[this.id] || {};
  }

  setPluginState(update) {
    const {
      plugins
    } = this.uppy.getState();
    this.uppy.setState({
      plugins: { ...plugins,
        [this.id]: { ...plugins[this.id],
          ...update
        }
      }
    });
  }

  setOptions(newOpts) {
    this.opts = { ...this.opts,
      ...newOpts
    };
    this.setPluginState(); // so that UI re-renders with new options

    this.i18nInit();
  }

  i18nInit() {
    const translator = new _uppy_utils_lib_Translator__WEBPACK_IMPORTED_MODULE_0__["default"]([this.defaultLocale, this.uppy.locale, this.opts.locale]);
    this.i18n = translator.translate.bind(translator);
    this.i18nArray = translator.translateArray.bind(translator);
    this.setPluginState(); // so that UI re-renders and we see the updated locale
  }
  /**
   * Extendable methods
   * ==================
   * These methods are here to serve as an overview of the extendable methods as well as
   * making them not conditional in use, such as `if (this.afterUpdate)`.
   */
  // eslint-disable-next-line class-methods-use-this


  addTarget() {
    throw new Error('Extend the addTarget method to add your plugin to another plugin\'s target');
  } // eslint-disable-next-line class-methods-use-this


  install() {} // eslint-disable-next-line class-methods-use-this


  uninstall() {}
  /**
   * Called when plugin is mounted, whether in DOM or into another plugin.
   * Needed because sometimes plugins are mounted separately/after `install`,
   * so this.el and this.parent might not be available in `install`.
   * This is the case with @uppy/react plugins, for example.
   */


  render() {
    throw new Error('Extend the render method to add your plugin to a DOM element');
  } // eslint-disable-next-line class-methods-use-this


  update() {} // Called after every state update, after everything's mounted. Debounced.
  // eslint-disable-next-line class-methods-use-this


  afterUpdate() {}

}

/***/ }),

/***/ "./node_modules/@uppy/core/lib/Restricter.js":
/*!***************************************************!*\
  !*** ./node_modules/@uppy/core/lib/Restricter.js ***!
  \***************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Restricter: () => (/* binding */ Restricter),
/* harmony export */   RestrictionError: () => (/* binding */ RestrictionError),
/* harmony export */   defaultOptions: () => (/* binding */ defaultOptions)
/* harmony export */ });
/* harmony import */ var _transloadit_prettier_bytes__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @transloadit/prettier-bytes */ "./node_modules/@transloadit/prettier-bytes/prettierBytes.js");
/* harmony import */ var mime_match__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! mime-match */ "./node_modules/mime-match/index.js");
/* eslint-disable max-classes-per-file, class-methods-use-this */


const defaultOptions = {
  maxFileSize: null,
  minFileSize: null,
  maxTotalFileSize: null,
  maxNumberOfFiles: null,
  minNumberOfFiles: null,
  allowedFileTypes: null,
  requiredMetaFields: []
};

class RestrictionError extends Error {
  constructor(message, _temp) {
    let {
      isUserFacing = true,
      file
    } = _temp === void 0 ? {} : _temp;
    super(message);
    this.isRestriction = true;
    this.isUserFacing = isUserFacing;
    if (file != null) this.file = file; // only some restriction errors are related to a particular file
  }

}

class Restricter {
  constructor(getOpts, i18n) {
    this.i18n = i18n;

    this.getOpts = () => {
      const opts = getOpts();

      if (opts.restrictions.allowedFileTypes != null && !Array.isArray(opts.restrictions.allowedFileTypes)) {
        throw new TypeError('`restrictions.allowedFileTypes` must be an array');
      }

      return opts;
    };
  } // Because these operations are slow, we cannot run them for every file (if we are adding multiple files)


  validateAggregateRestrictions(existingFiles, addingFiles) {
    const {
      maxTotalFileSize,
      maxNumberOfFiles
    } = this.getOpts().restrictions;

    if (maxNumberOfFiles) {
      const nonGhostFiles = existingFiles.filter(f => !f.isGhost);

      if (nonGhostFiles.length + addingFiles.length > maxNumberOfFiles) {
        throw new RestrictionError(`${this.i18n('youCanOnlyUploadX', {
          smart_count: maxNumberOfFiles
        })}`);
      }
    }

    if (maxTotalFileSize) {
      let totalFilesSize = existingFiles.reduce((total, f) => total + f.size, 0);

      for (const addingFile of addingFiles) {
        if (addingFile.size != null) {
          // We can't check maxTotalFileSize if the size is unknown.
          totalFilesSize += addingFile.size;

          if (totalFilesSize > maxTotalFileSize) {
            throw new RestrictionError(this.i18n('exceedsSize', {
              size: _transloadit_prettier_bytes__WEBPACK_IMPORTED_MODULE_0__(maxTotalFileSize),
              file: addingFile.name
            }));
          }
        }
      }
    }
  }

  validateSingleFile(file) {
    const {
      maxFileSize,
      minFileSize,
      allowedFileTypes
    } = this.getOpts().restrictions;

    if (allowedFileTypes) {
      const isCorrectFileType = allowedFileTypes.some(type => {
        // check if this is a mime-type
        if (type.includes('/')) {
          if (!file.type) return false;
          return mime_match__WEBPACK_IMPORTED_MODULE_1__(file.type.replace(/;.*?$/, ''), type);
        } // otherwise this is likely an extension


        if (type[0] === '.' && file.extension) {
          return file.extension.toLowerCase() === type.slice(1).toLowerCase();
        }

        return false;
      });

      if (!isCorrectFileType) {
        const allowedFileTypesString = allowedFileTypes.join(', ');
        throw new RestrictionError(this.i18n('youCanOnlyUploadFileTypes', {
          types: allowedFileTypesString
        }), {
          file
        });
      }
    } // We can't check maxFileSize if the size is unknown.


    if (maxFileSize && file.size != null && file.size > maxFileSize) {
      throw new RestrictionError(this.i18n('exceedsSize', {
        size: _transloadit_prettier_bytes__WEBPACK_IMPORTED_MODULE_0__(maxFileSize),
        file: file.name
      }), {
        file
      });
    } // We can't check minFileSize if the size is unknown.


    if (minFileSize && file.size != null && file.size < minFileSize) {
      throw new RestrictionError(this.i18n('inferiorSize', {
        size: _transloadit_prettier_bytes__WEBPACK_IMPORTED_MODULE_0__(minFileSize)
      }), {
        file
      });
    }
  }

  validate(existingFiles, addingFiles) {
    addingFiles.forEach(addingFile => {
      this.validateSingleFile(addingFile);
    });
    this.validateAggregateRestrictions(existingFiles, addingFiles);
  }

  validateMinNumberOfFiles(files) {
    const {
      minNumberOfFiles
    } = this.getOpts().restrictions;

    if (Object.keys(files).length < minNumberOfFiles) {
      throw new RestrictionError(this.i18n('youHaveToAtLeastSelectX', {
        smart_count: minNumberOfFiles
      }));
    }
  }

  getMissingRequiredMetaFields(file) {
    const error = new RestrictionError(this.i18n('missingRequiredMetaFieldOnFile', {
      fileName: file.name
    }));
    const {
      requiredMetaFields
    } = this.getOpts().restrictions;
    const missingFields = [];

    for (const field of requiredMetaFields) {
      if (!Object.hasOwn(file.meta, field) || file.meta[field] === '') {
        missingFields.push(field);
      }
    }

    return {
      missingFields,
      error
    };
  }

}



/***/ }),

/***/ "./node_modules/@uppy/core/lib/UIPlugin.js":
/*!*************************************************!*\
  !*** ./node_modules/@uppy/core/lib/UIPlugin.js ***!
  \*************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./node_modules/preact/dist/preact.module.js");
/* harmony import */ var _uppy_utils_lib_findDOMElement__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @uppy/utils/lib/findDOMElement */ "./node_modules/@uppy/utils/lib/findDOMElement.js");
/* harmony import */ var _uppy_utils_lib_getTextDirection__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @uppy/utils/lib/getTextDirection */ "./node_modules/@uppy/utils/lib/getTextDirection.js");
/* harmony import */ var _BasePlugin_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./BasePlugin.js */ "./node_modules/@uppy/core/lib/BasePlugin.js");
function _classPrivateFieldLooseBase(receiver, privateKey) { if (!Object.prototype.hasOwnProperty.call(receiver, privateKey)) { throw new TypeError("attempted to use private field on non-instance"); } return receiver; }

var id = 0;

function _classPrivateFieldLooseKey(name) { return "__private_" + id++ + "_" + name; }





/**
 * Defer a frequent call to the microtask queue.
 *
 * @param {() => T} fn
 * @returns {Promise<T>}
 */

function debounce(fn) {
  let calling = null;
  let latestArgs = null;
  return function () {
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    latestArgs = args;

    if (!calling) {
      calling = Promise.resolve().then(() => {
        calling = null; // At this point `args` may be different from the most
        // recent state, if multiple calls happened since this task
        // was queued. So we use the `latestArgs`, which definitely
        // is the most recent call.

        return fn(...latestArgs);
      });
    }

    return calling;
  };
}
/**
 * UIPlugin is the extended version of BasePlugin to incorporate rendering with Preact.
 * Use this for plugins that need a user interface.
 *
 * For plugins without an user interface, see BasePlugin.
 */


var _updateUI = /*#__PURE__*/_classPrivateFieldLooseKey("updateUI");

class UIPlugin extends _BasePlugin_js__WEBPACK_IMPORTED_MODULE_3__["default"] {
  constructor() {
    super(...arguments);
    Object.defineProperty(this, _updateUI, {
      writable: true,
      value: void 0
    });
  }

  getTargetPlugin(target) {
    let targetPlugin;

    if (typeof target === 'object' && target instanceof UIPlugin) {
      // Targeting a plugin *instance*
      targetPlugin = target;
    } else if (typeof target === 'function') {
      // Targeting a plugin type
      const Target = target; // Find the target plugin instance.

      this.uppy.iteratePlugins(p => {
        if (p instanceof Target) {
          targetPlugin = p;
        }
      });
    }

    return targetPlugin;
  }
  /**
   * Check if supplied `target` is a DOM element or an `object`.
   * If it’s an object — target is a plugin, and we search `plugins`
   * for a plugin with same name and return its target.
   */


  mount(target, plugin) {
    const callerPluginName = plugin.id;
    const targetElement = (0,_uppy_utils_lib_findDOMElement__WEBPACK_IMPORTED_MODULE_1__["default"])(target);

    if (targetElement) {
      this.isTargetDOMEl = true; // When target is <body> with a single <div> element,
      // Preact thinks it’s the Uppy root element in there when doing a diff,
      // and destroys it. So we are creating a fragment (could be empty div)

      const uppyRootElement = document.createElement('div');
      uppyRootElement.classList.add('uppy-Root'); // API for plugins that require a synchronous rerender.

      _classPrivateFieldLooseBase(this, _updateUI)[_updateUI] = debounce(state => {
        // plugin could be removed, but this.rerender is debounced below,
        // so it could still be called even after uppy.removePlugin or uppy.close
        // hence the check
        if (!this.uppy.getPlugin(this.id)) return;
        (0,preact__WEBPACK_IMPORTED_MODULE_0__.render)(this.render(state), uppyRootElement);
        this.afterUpdate();
      });
      this.uppy.log(`Installing ${callerPluginName} to a DOM element '${target}'`);

      if (this.opts.replaceTargetContent) {
        // Doing render(h(null), targetElement), which should have been
        // a better way, since because the component might need to do additional cleanup when it is removed,
        // stopped working — Preact just adds null into target, not replacing
        targetElement.innerHTML = '';
      }

      (0,preact__WEBPACK_IMPORTED_MODULE_0__.render)(this.render(this.uppy.getState()), uppyRootElement);
      this.el = uppyRootElement;
      targetElement.appendChild(uppyRootElement); // Set the text direction if the page has not defined one.

      uppyRootElement.dir = this.opts.direction || (0,_uppy_utils_lib_getTextDirection__WEBPACK_IMPORTED_MODULE_2__["default"])(uppyRootElement) || 'ltr';
      this.onMount();
      return this.el;
    }

    const targetPlugin = this.getTargetPlugin(target);

    if (targetPlugin) {
      this.uppy.log(`Installing ${callerPluginName} to ${targetPlugin.id}`);
      this.parent = targetPlugin;
      this.el = targetPlugin.addTarget(plugin);
      this.onMount();
      return this.el;
    }

    this.uppy.log(`Not installing ${callerPluginName}`);
    let message = `Invalid target option given to ${callerPluginName}.`;

    if (typeof target === 'function') {
      message += ' The given target is not a Plugin class. ' + 'Please check that you\'re not specifying a React Component instead of a plugin. ' + 'If you are using @uppy/* packages directly, make sure you have only 1 version of @uppy/core installed: ' + 'run `npm ls @uppy/core` on the command line and verify that all the versions match and are deduped correctly.';
    } else {
      message += 'If you meant to target an HTML element, please make sure that the element exists. ' + 'Check that the <script> tag initializing Uppy is right before the closing </body> tag at the end of the page. ' + '(see https://github.com/transloadit/uppy/issues/1042)\n\n' + 'If you meant to target a plugin, please confirm that your `import` statements or `require` calls are correct.';
    }

    throw new Error(message);
  }

  update(state) {
    if (this.el != null) {
      var _classPrivateFieldLoo, _classPrivateFieldLoo2;

      (_classPrivateFieldLoo = (_classPrivateFieldLoo2 = _classPrivateFieldLooseBase(this, _updateUI))[_updateUI]) == null ? void 0 : _classPrivateFieldLoo.call(_classPrivateFieldLoo2, state);
    }
  }

  unmount() {
    if (this.isTargetDOMEl) {
      var _this$el;

      (_this$el = this.el) == null ? void 0 : _this$el.remove();
    }

    this.onUnmount();
  } // eslint-disable-next-line class-methods-use-this


  onMount() {} // eslint-disable-next-line class-methods-use-this


  onUnmount() {}

}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (UIPlugin);

/***/ }),

/***/ "./node_modules/@uppy/core/lib/Uppy.js":
/*!*********************************************!*\
  !*** ./node_modules/@uppy/core/lib/Uppy.js ***!
  \*********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _uppy_utils_lib_Translator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @uppy/utils/lib/Translator */ "./node_modules/@uppy/utils/lib/Translator.js");
/* harmony import */ var namespace_emitter__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! namespace-emitter */ "./node_modules/namespace-emitter/index.js");
/* harmony import */ var nanoid_non_secure__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! nanoid/non-secure */ "./node_modules/nanoid/non-secure/index.js");
/* harmony import */ var lodash_throttle__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! lodash.throttle */ "./node_modules/lodash.throttle/index.js");
/* harmony import */ var _uppy_store_default__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @uppy/store-default */ "./node_modules/@uppy/store-default/lib/index.js");
/* harmony import */ var _uppy_utils_lib_getFileType__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @uppy/utils/lib/getFileType */ "./node_modules/@uppy/utils/lib/getFileType.js");
/* harmony import */ var _uppy_utils_lib_getFileNameAndExtension__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @uppy/utils/lib/getFileNameAndExtension */ "./node_modules/@uppy/utils/lib/getFileNameAndExtension.js");
/* harmony import */ var _uppy_utils_lib_generateFileID__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @uppy/utils/lib/generateFileID */ "./node_modules/@uppy/utils/lib/generateFileID.js");
/* harmony import */ var _supportsUploadProgress_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./supportsUploadProgress.js */ "./node_modules/@uppy/core/lib/supportsUploadProgress.js");
/* harmony import */ var _getFileName_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./getFileName.js */ "./node_modules/@uppy/core/lib/getFileName.js");
/* harmony import */ var _loggers_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./loggers.js */ "./node_modules/@uppy/core/lib/loggers.js");
/* harmony import */ var _Restricter_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./Restricter.js */ "./node_modules/@uppy/core/lib/Restricter.js");
/* harmony import */ var _locale_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./locale.js */ "./node_modules/@uppy/core/lib/locale.js");
let _Symbol$for, _Symbol$for2;

function _classPrivateFieldLooseBase(receiver, privateKey) { if (!Object.prototype.hasOwnProperty.call(receiver, privateKey)) { throw new TypeError("attempted to use private field on non-instance"); } return receiver; }

var id = 0;

function _classPrivateFieldLooseKey(name) { return "__private_" + id++ + "_" + name; }

/* eslint-disable max-classes-per-file */

/* global AggregateError */












const packageJson = {
  "version": "3.2.0"
};

/**
 * Uppy Core module.
 * Manages plugins, state updates, acts as an event bus,
 * adds/removes files and metadata.
 */

var _plugins = /*#__PURE__*/_classPrivateFieldLooseKey("plugins");

var _restricter = /*#__PURE__*/_classPrivateFieldLooseKey("restricter");

var _storeUnsubscribe = /*#__PURE__*/_classPrivateFieldLooseKey("storeUnsubscribe");

var _emitter = /*#__PURE__*/_classPrivateFieldLooseKey("emitter");

var _preProcessors = /*#__PURE__*/_classPrivateFieldLooseKey("preProcessors");

var _uploaders = /*#__PURE__*/_classPrivateFieldLooseKey("uploaders");

var _postProcessors = /*#__PURE__*/_classPrivateFieldLooseKey("postProcessors");

var _informAndEmit = /*#__PURE__*/_classPrivateFieldLooseKey("informAndEmit");

var _checkRequiredMetaFieldsOnFile = /*#__PURE__*/_classPrivateFieldLooseKey("checkRequiredMetaFieldsOnFile");

var _checkRequiredMetaFields = /*#__PURE__*/_classPrivateFieldLooseKey("checkRequiredMetaFields");

var _assertNewUploadAllowed = /*#__PURE__*/_classPrivateFieldLooseKey("assertNewUploadAllowed");

var _transformFile = /*#__PURE__*/_classPrivateFieldLooseKey("transformFile");

var _startIfAutoProceed = /*#__PURE__*/_classPrivateFieldLooseKey("startIfAutoProceed");

var _checkAndUpdateFileState = /*#__PURE__*/_classPrivateFieldLooseKey("checkAndUpdateFileState");

var _addListeners = /*#__PURE__*/_classPrivateFieldLooseKey("addListeners");

var _updateOnlineStatus = /*#__PURE__*/_classPrivateFieldLooseKey("updateOnlineStatus");

var _createUpload = /*#__PURE__*/_classPrivateFieldLooseKey("createUpload");

var _getUpload = /*#__PURE__*/_classPrivateFieldLooseKey("getUpload");

var _removeUpload = /*#__PURE__*/_classPrivateFieldLooseKey("removeUpload");

var _runUpload = /*#__PURE__*/_classPrivateFieldLooseKey("runUpload");

_Symbol$for = Symbol.for('uppy test: getPlugins');
_Symbol$for2 = Symbol.for('uppy test: createUpload');

class Uppy {
  /** @type {Record<string, BasePlugin[]>} */

  /**
   * Instantiate Uppy
   *
   * @param {object} opts — Uppy options
   */
  constructor(_opts) {
    Object.defineProperty(this, _runUpload, {
      value: _runUpload2
    });
    Object.defineProperty(this, _removeUpload, {
      value: _removeUpload2
    });
    Object.defineProperty(this, _getUpload, {
      value: _getUpload2
    });
    Object.defineProperty(this, _createUpload, {
      value: _createUpload2
    });
    Object.defineProperty(this, _addListeners, {
      value: _addListeners2
    });
    Object.defineProperty(this, _checkAndUpdateFileState, {
      value: _checkAndUpdateFileState2
    });
    Object.defineProperty(this, _startIfAutoProceed, {
      value: _startIfAutoProceed2
    });
    Object.defineProperty(this, _transformFile, {
      value: _transformFile2
    });
    Object.defineProperty(this, _assertNewUploadAllowed, {
      value: _assertNewUploadAllowed2
    });
    Object.defineProperty(this, _checkRequiredMetaFields, {
      value: _checkRequiredMetaFields2
    });
    Object.defineProperty(this, _checkRequiredMetaFieldsOnFile, {
      value: _checkRequiredMetaFieldsOnFile2
    });
    Object.defineProperty(this, _informAndEmit, {
      value: _informAndEmit2
    });
    Object.defineProperty(this, _plugins, {
      writable: true,
      value: Object.create(null)
    });
    Object.defineProperty(this, _restricter, {
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, _storeUnsubscribe, {
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, _emitter, {
      writable: true,
      value: namespace_emitter__WEBPACK_IMPORTED_MODULE_1__()
    });
    Object.defineProperty(this, _preProcessors, {
      writable: true,
      value: new Set()
    });
    Object.defineProperty(this, _uploaders, {
      writable: true,
      value: new Set()
    });
    Object.defineProperty(this, _postProcessors, {
      writable: true,
      value: new Set()
    });
    Object.defineProperty(this, _updateOnlineStatus, {
      writable: true,
      value: this.updateOnlineStatus.bind(this)
    });
    this.defaultLocale = _locale_js__WEBPACK_IMPORTED_MODULE_11__["default"];
    const defaultOptions = {
      id: 'uppy',
      autoProceed: false,
      allowMultipleUploadBatches: true,
      debug: false,
      restrictions: _Restricter_js__WEBPACK_IMPORTED_MODULE_10__.defaultOptions,
      meta: {},
      onBeforeFileAdded: currentFile => currentFile,
      onBeforeUpload: files => files,
      store: new _uppy_store_default__WEBPACK_IMPORTED_MODULE_3__["default"](),
      logger: _loggers_js__WEBPACK_IMPORTED_MODULE_9__.justErrorsLogger,
      infoTimeout: 5000
    }; // Merge default options with the ones set by user,
    // making sure to merge restrictions too

    this.opts = { ...defaultOptions,
      ..._opts,
      restrictions: { ...defaultOptions.restrictions,
        ...(_opts && _opts.restrictions)
      }
    }; // Support debug: true for backwards-compatability, unless logger is set in opts
    // opts instead of this.opts to avoid comparing objects — we set logger: justErrorsLogger in defaultOptions

    if (_opts && _opts.logger && _opts.debug) {
      this.log('You are using a custom `logger`, but also set `debug: true`, which uses built-in logger to output logs to console. Ignoring `debug: true` and using your custom `logger`.', 'warning');
    } else if (_opts && _opts.debug) {
      this.opts.logger = _loggers_js__WEBPACK_IMPORTED_MODULE_9__.debugLogger;
    }

    this.log(`Using Core v${this.constructor.VERSION}`);
    this.i18nInit(); // ___Why throttle at 500ms?
    //    - We must throttle at >250ms for superfocus in Dashboard to work well
    //    (because animation takes 0.25s, and we want to wait for all animations to be over before refocusing).
    //    [Practical Check]: if thottle is at 100ms, then if you are uploading a file,
    //    and click 'ADD MORE FILES', - focus won't activate in Firefox.
    //    - We must throttle at around >500ms to avoid performance lags.
    //    [Practical Check] Firefox, try to upload a big file for a prolonged period of time. Laptop will start to heat up.

    this.calculateProgress = lodash_throttle__WEBPACK_IMPORTED_MODULE_2__(this.calculateProgress.bind(this), 500, {
      leading: true,
      trailing: true
    });
    this.store = this.opts.store;
    this.setState({
      plugins: {},
      files: {},
      currentUploads: {},
      allowNewUpload: true,
      capabilities: {
        uploadProgress: (0,_supportsUploadProgress_js__WEBPACK_IMPORTED_MODULE_7__["default"])(),
        individualCancellation: true,
        resumableUploads: false
      },
      totalProgress: 0,
      meta: { ...this.opts.meta
      },
      info: [],
      recoveredState: null
    });
    _classPrivateFieldLooseBase(this, _restricter)[_restricter] = new _Restricter_js__WEBPACK_IMPORTED_MODULE_10__.Restricter(() => this.opts, this.i18n);
    _classPrivateFieldLooseBase(this, _storeUnsubscribe)[_storeUnsubscribe] = this.store.subscribe((prevState, nextState, patch) => {
      this.emit('state-update', prevState, nextState, patch);
      this.updateAll(nextState);
    }); // Exposing uppy object on window for debugging and testing

    if (this.opts.debug && typeof window !== 'undefined') {
      window[this.opts.id] = this;
    }

    _classPrivateFieldLooseBase(this, _addListeners)[_addListeners]();
  }

  emit(event) {
    for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
      args[_key - 1] = arguments[_key];
    }

    _classPrivateFieldLooseBase(this, _emitter)[_emitter].emit(event, ...args);
  }

  on(event, callback) {
    _classPrivateFieldLooseBase(this, _emitter)[_emitter].on(event, callback);

    return this;
  }

  once(event, callback) {
    _classPrivateFieldLooseBase(this, _emitter)[_emitter].once(event, callback);

    return this;
  }

  off(event, callback) {
    _classPrivateFieldLooseBase(this, _emitter)[_emitter].off(event, callback);

    return this;
  }
  /**
   * Iterate on all plugins and run `update` on them.
   * Called each time state changes.
   *
   */


  updateAll(state) {
    this.iteratePlugins(plugin => {
      plugin.update(state);
    });
  }
  /**
   * Updates state with a patch
   *
   * @param {object} patch {foo: 'bar'}
   */


  setState(patch) {
    this.store.setState(patch);
  }
  /**
   * Returns current state.
   *
   * @returns {object}
   */


  getState() {
    return this.store.getState();
  }

  patchFilesState(filesWithNewState) {
    const existingFilesState = this.getState().files;
    this.setState({
      files: { ...existingFilesState,
        ...Object.fromEntries(Object.entries(filesWithNewState).map(_ref => {
          let [fileID, newFileState] = _ref;
          return [fileID, { ...existingFilesState[fileID],
            ...newFileState
          }];
        }))
      }
    });
  }
  /**
   * Shorthand to set state for a specific file.
   */


  setFileState(fileID, state) {
    if (!this.getState().files[fileID]) {
      throw new Error(`Can’t set state for ${fileID} (the file could have been removed)`);
    }

    this.patchFilesState({
      [fileID]: state
    });
  }

  i18nInit() {
    const translator = new _uppy_utils_lib_Translator__WEBPACK_IMPORTED_MODULE_0__["default"]([this.defaultLocale, this.opts.locale]);
    this.i18n = translator.translate.bind(translator);
    this.i18nArray = translator.translateArray.bind(translator);
    this.locale = translator.locale;
  }

  setOptions(newOpts) {
    this.opts = { ...this.opts,
      ...newOpts,
      restrictions: { ...this.opts.restrictions,
        ...(newOpts && newOpts.restrictions)
      }
    };

    if (newOpts.meta) {
      this.setMeta(newOpts.meta);
    }

    this.i18nInit();

    if (newOpts.locale) {
      this.iteratePlugins(plugin => {
        plugin.setOptions(newOpts);
      });
    } // Note: this is not the preact `setState`, it's an internal function that has the same name.


    this.setState(); // so that UI re-renders with new options
  }

  resetProgress() {
    const defaultProgress = {
      percentage: 0,
      bytesUploaded: 0,
      uploadComplete: false,
      uploadStarted: null
    };
    const files = { ...this.getState().files
    };
    const updatedFiles = {};
    Object.keys(files).forEach(fileID => {
      updatedFiles[fileID] = { ...files[fileID],
        progress: { ...files[fileID].progress,
          ...defaultProgress
        }
      };
    });
    this.setState({
      files: updatedFiles,
      totalProgress: 0,
      allowNewUpload: true,
      error: null,
      recoveredState: null
    });
    this.emit('reset-progress');
  }

  addPreProcessor(fn) {
    _classPrivateFieldLooseBase(this, _preProcessors)[_preProcessors].add(fn);
  }

  removePreProcessor(fn) {
    return _classPrivateFieldLooseBase(this, _preProcessors)[_preProcessors].delete(fn);
  }

  addPostProcessor(fn) {
    _classPrivateFieldLooseBase(this, _postProcessors)[_postProcessors].add(fn);
  }

  removePostProcessor(fn) {
    return _classPrivateFieldLooseBase(this, _postProcessors)[_postProcessors].delete(fn);
  }

  addUploader(fn) {
    _classPrivateFieldLooseBase(this, _uploaders)[_uploaders].add(fn);
  }

  removeUploader(fn) {
    return _classPrivateFieldLooseBase(this, _uploaders)[_uploaders].delete(fn);
  }

  setMeta(data) {
    const updatedMeta = { ...this.getState().meta,
      ...data
    };
    const updatedFiles = { ...this.getState().files
    };
    Object.keys(updatedFiles).forEach(fileID => {
      updatedFiles[fileID] = { ...updatedFiles[fileID],
        meta: { ...updatedFiles[fileID].meta,
          ...data
        }
      };
    });
    this.log('Adding metadata:');
    this.log(data);
    this.setState({
      meta: updatedMeta,
      files: updatedFiles
    });
  }

  setFileMeta(fileID, data) {
    const updatedFiles = { ...this.getState().files
    };

    if (!updatedFiles[fileID]) {
      this.log('Was trying to set metadata for a file that has been removed: ', fileID);
      return;
    }

    const newMeta = { ...updatedFiles[fileID].meta,
      ...data
    };
    updatedFiles[fileID] = { ...updatedFiles[fileID],
      meta: newMeta
    };
    this.setState({
      files: updatedFiles
    });
  }
  /**
   * Get a file object.
   *
   * @param {string} fileID The ID of the file object to return.
   */


  getFile(fileID) {
    return this.getState().files[fileID];
  }
  /**
   * Get all files in an array.
   */


  getFiles() {
    const {
      files
    } = this.getState();
    return Object.values(files);
  }

  getFilesByIds(ids) {
    return ids.map(id => this.getFile(id));
  }

  getObjectOfFilesPerState() {
    const {
      files: filesObject,
      totalProgress,
      error
    } = this.getState();
    const files = Object.values(filesObject);
    const inProgressFiles = files.filter(_ref2 => {
      let {
        progress
      } = _ref2;
      return !progress.uploadComplete && progress.uploadStarted;
    });
    const newFiles = files.filter(file => !file.progress.uploadStarted);
    const startedFiles = files.filter(file => file.progress.uploadStarted || file.progress.preprocess || file.progress.postprocess);
    const uploadStartedFiles = files.filter(file => file.progress.uploadStarted);
    const pausedFiles = files.filter(file => file.isPaused);
    const completeFiles = files.filter(file => file.progress.uploadComplete);
    const erroredFiles = files.filter(file => file.error);
    const inProgressNotPausedFiles = inProgressFiles.filter(file => !file.isPaused);
    const processingFiles = files.filter(file => file.progress.preprocess || file.progress.postprocess);
    return {
      newFiles,
      startedFiles,
      uploadStartedFiles,
      pausedFiles,
      completeFiles,
      erroredFiles,
      inProgressFiles,
      inProgressNotPausedFiles,
      processingFiles,
      isUploadStarted: uploadStartedFiles.length > 0,
      isAllComplete: totalProgress === 100 && completeFiles.length === files.length && processingFiles.length === 0,
      isAllErrored: !!error && erroredFiles.length === files.length,
      isAllPaused: inProgressFiles.length !== 0 && pausedFiles.length === inProgressFiles.length,
      isUploadInProgress: inProgressFiles.length > 0,
      isSomeGhost: files.some(file => file.isGhost)
    };
  }
  /*
  * @constructs
  * @param { Error[] } errors
  * @param { undefined } file
  */

  /*
  * @constructs
  * @param { RestrictionError } error
  */


  validateRestrictions(file, files) {
    if (files === void 0) {
      files = this.getFiles();
    }

    try {
      _classPrivateFieldLooseBase(this, _restricter)[_restricter].validate(files, [file]);
    } catch (err) {
      return err;
    }

    return null;
  }

  checkIfFileAlreadyExists(fileID) {
    const {
      files
    } = this.getState();

    if (files[fileID] && !files[fileID].isGhost) {
      return true;
    }

    return false;
  }
  /**
   * Create a file state object based on user-provided `addFile()` options.
   */


  /**
   * Add a new file to `state.files`. This will run `onBeforeFileAdded`,
   * try to guess file type in a clever way, check file against restrictions,
   * and start an upload if `autoProceed === true`.
   *
   * @param {object} file object to add
   * @returns {string} id for the added file
   */
  addFile(file) {
    _classPrivateFieldLooseBase(this, _assertNewUploadAllowed)[_assertNewUploadAllowed](file);

    const {
      nextFilesState,
      validFilesToAdd,
      errors
    } = _classPrivateFieldLooseBase(this, _checkAndUpdateFileState)[_checkAndUpdateFileState]([file]);

    const restrictionErrors = errors.filter(error => error.isRestriction);

    _classPrivateFieldLooseBase(this, _informAndEmit)[_informAndEmit](restrictionErrors);

    if (errors.length > 0) throw errors[0];
    this.setState({
      files: nextFilesState
    });
    const [firstValidFileToAdd] = validFilesToAdd;
    this.emit('file-added', firstValidFileToAdd);
    this.emit('files-added', validFilesToAdd);
    this.log(`Added file: ${firstValidFileToAdd.name}, ${firstValidFileToAdd.id}, mime type: ${firstValidFileToAdd.type}`);

    _classPrivateFieldLooseBase(this, _startIfAutoProceed)[_startIfAutoProceed]();

    return firstValidFileToAdd.id;
  }
  /**
   * Add multiple files to `state.files`. See the `addFile()` documentation.
   *
   * If an error occurs while adding a file, it is logged and the user is notified.
   * This is good for UI plugins, but not for programmatic use.
   * Programmatic users should usually still use `addFile()` on individual files.
   */


  addFiles(fileDescriptors) {
    _classPrivateFieldLooseBase(this, _assertNewUploadAllowed)[_assertNewUploadAllowed]();

    const {
      nextFilesState,
      validFilesToAdd,
      errors
    } = _classPrivateFieldLooseBase(this, _checkAndUpdateFileState)[_checkAndUpdateFileState](fileDescriptors);

    const restrictionErrors = errors.filter(error => error.isRestriction);

    _classPrivateFieldLooseBase(this, _informAndEmit)[_informAndEmit](restrictionErrors);

    const nonRestrictionErrors = errors.filter(error => !error.isRestriction);

    if (nonRestrictionErrors.length > 0) {
      let message = 'Multiple errors occurred while adding files:\n';
      nonRestrictionErrors.forEach(subError => {
        message += `\n * ${subError.message}`;
      });
      this.info({
        message: this.i18n('addBulkFilesFailed', {
          smart_count: nonRestrictionErrors.length
        }),
        details: message
      }, 'error', this.opts.infoTimeout);

      if (typeof AggregateError === 'function') {
        throw new AggregateError(nonRestrictionErrors, message);
      } else {
        const err = new Error(message);
        err.errors = nonRestrictionErrors;
        throw err;
      }
    } // OK, we haven't thrown an error, we can start updating state and emitting events now:


    this.setState({
      files: nextFilesState
    });
    validFilesToAdd.forEach(file => {
      this.emit('file-added', file);
    });
    this.emit('files-added', validFilesToAdd);

    if (validFilesToAdd.length > 5) {
      this.log(`Added batch of ${validFilesToAdd.length} files`);
    } else {
      Object.values(validFilesToAdd).forEach(file => {
        this.log(`Added file: ${file.name}\n id: ${file.id}\n type: ${file.type}`);
      });
    }

    if (validFilesToAdd.length > 0) {
      _classPrivateFieldLooseBase(this, _startIfAutoProceed)[_startIfAutoProceed]();
    }
  }

  removeFiles(fileIDs, reason) {
    const {
      files,
      currentUploads
    } = this.getState();
    const updatedFiles = { ...files
    };
    const updatedUploads = { ...currentUploads
    };
    const removedFiles = Object.create(null);
    fileIDs.forEach(fileID => {
      if (files[fileID]) {
        removedFiles[fileID] = files[fileID];
        delete updatedFiles[fileID];
      }
    }); // Remove files from the `fileIDs` list in each upload.

    function fileIsNotRemoved(uploadFileID) {
      return removedFiles[uploadFileID] === undefined;
    }

    Object.keys(updatedUploads).forEach(uploadID => {
      const newFileIDs = currentUploads[uploadID].fileIDs.filter(fileIsNotRemoved); // Remove the upload if no files are associated with it anymore.

      if (newFileIDs.length === 0) {
        delete updatedUploads[uploadID];
        return;
      }

      const {
        capabilities
      } = this.getState();

      if (newFileIDs.length !== currentUploads[uploadID].fileIDs.length && !capabilities.individualCancellation) {
        throw new Error('individualCancellation is disabled');
      }

      updatedUploads[uploadID] = { ...currentUploads[uploadID],
        fileIDs: newFileIDs
      };
    });
    const stateUpdate = {
      currentUploads: updatedUploads,
      files: updatedFiles
    }; // If all files were removed - allow new uploads,
    // and clear recoveredState

    if (Object.keys(updatedFiles).length === 0) {
      stateUpdate.allowNewUpload = true;
      stateUpdate.error = null;
      stateUpdate.recoveredState = null;
    }

    this.setState(stateUpdate);
    this.calculateTotalProgress();
    const removedFileIDs = Object.keys(removedFiles);
    removedFileIDs.forEach(fileID => {
      this.emit('file-removed', removedFiles[fileID], reason);
    });

    if (removedFileIDs.length > 5) {
      this.log(`Removed ${removedFileIDs.length} files`);
    } else {
      this.log(`Removed files: ${removedFileIDs.join(', ')}`);
    }
  }

  removeFile(fileID, reason) {
    if (reason === void 0) {
      reason = null;
    }

    this.removeFiles([fileID], reason);
  }

  pauseResume(fileID) {
    if (!this.getState().capabilities.resumableUploads || this.getFile(fileID).uploadComplete) {
      return undefined;
    }

    const wasPaused = this.getFile(fileID).isPaused || false;
    const isPaused = !wasPaused;
    this.setFileState(fileID, {
      isPaused
    });
    this.emit('upload-pause', fileID, isPaused);
    return isPaused;
  }

  pauseAll() {
    const updatedFiles = { ...this.getState().files
    };
    const inProgressUpdatedFiles = Object.keys(updatedFiles).filter(file => {
      return !updatedFiles[file].progress.uploadComplete && updatedFiles[file].progress.uploadStarted;
    });
    inProgressUpdatedFiles.forEach(file => {
      const updatedFile = { ...updatedFiles[file],
        isPaused: true
      };
      updatedFiles[file] = updatedFile;
    });
    this.setState({
      files: updatedFiles
    });
    this.emit('pause-all');
  }

  resumeAll() {
    const updatedFiles = { ...this.getState().files
    };
    const inProgressUpdatedFiles = Object.keys(updatedFiles).filter(file => {
      return !updatedFiles[file].progress.uploadComplete && updatedFiles[file].progress.uploadStarted;
    });
    inProgressUpdatedFiles.forEach(file => {
      const updatedFile = { ...updatedFiles[file],
        isPaused: false,
        error: null
      };
      updatedFiles[file] = updatedFile;
    });
    this.setState({
      files: updatedFiles
    });
    this.emit('resume-all');
  }

  retryAll() {
    const updatedFiles = { ...this.getState().files
    };
    const filesToRetry = Object.keys(updatedFiles).filter(file => {
      return updatedFiles[file].error;
    });
    filesToRetry.forEach(file => {
      const updatedFile = { ...updatedFiles[file],
        isPaused: false,
        error: null
      };
      updatedFiles[file] = updatedFile;
    });
    this.setState({
      files: updatedFiles,
      error: null
    });
    this.emit('retry-all', filesToRetry);

    if (filesToRetry.length === 0) {
      return Promise.resolve({
        successful: [],
        failed: []
      });
    }

    const uploadID = _classPrivateFieldLooseBase(this, _createUpload)[_createUpload](filesToRetry, {
      forceAllowNewUpload: true // create new upload even if allowNewUpload: false

    });

    return _classPrivateFieldLooseBase(this, _runUpload)[_runUpload](uploadID);
  }

  cancelAll(_temp) {
    let {
      reason = 'user'
    } = _temp === void 0 ? {} : _temp;
    this.emit('cancel-all', {
      reason
    }); // Only remove existing uploads if user is canceling

    if (reason === 'user') {
      const {
        files
      } = this.getState();
      const fileIDs = Object.keys(files);

      if (fileIDs.length) {
        this.removeFiles(fileIDs, 'cancel-all');
      }

      this.setState({
        totalProgress: 0,
        error: null,
        recoveredState: null
      });
    }
  }

  retryUpload(fileID) {
    this.setFileState(fileID, {
      error: null,
      isPaused: false
    });
    this.emit('upload-retry', fileID);

    const uploadID = _classPrivateFieldLooseBase(this, _createUpload)[_createUpload]([fileID], {
      forceAllowNewUpload: true // create new upload even if allowNewUpload: false

    });

    return _classPrivateFieldLooseBase(this, _runUpload)[_runUpload](uploadID);
  }

  logout() {
    this.iteratePlugins(plugin => {
      if (plugin.provider && plugin.provider.logout) {
        plugin.provider.logout();
      }
    });
  }

  calculateProgress(file, data) {
    if (file == null || !this.getFile(file.id)) {
      this.log(`Not setting progress for a file that has been removed: ${file == null ? void 0 : file.id}`);
      return;
    } // bytesTotal may be null or zero; in that case we can't divide by it


    const canHavePercentage = Number.isFinite(data.bytesTotal) && data.bytesTotal > 0;
    this.setFileState(file.id, {
      progress: { ...this.getFile(file.id).progress,
        bytesUploaded: data.bytesUploaded,
        bytesTotal: data.bytesTotal,
        percentage: canHavePercentage ? Math.round(data.bytesUploaded / data.bytesTotal * 100) : 0
      }
    });
    this.calculateTotalProgress();
  }

  calculateTotalProgress() {
    // calculate total progress, using the number of files currently uploading,
    // multiplied by 100 and the summ of individual progress of each file
    const files = this.getFiles();
    const inProgress = files.filter(file => {
      return file.progress.uploadStarted || file.progress.preprocess || file.progress.postprocess;
    });

    if (inProgress.length === 0) {
      this.emit('progress', 0);
      this.setState({
        totalProgress: 0
      });
      return;
    }

    const sizedFiles = inProgress.filter(file => file.progress.bytesTotal != null);
    const unsizedFiles = inProgress.filter(file => file.progress.bytesTotal == null);

    if (sizedFiles.length === 0) {
      const progressMax = inProgress.length * 100;
      const currentProgress = unsizedFiles.reduce((acc, file) => {
        return acc + file.progress.percentage;
      }, 0);
      const totalProgress = Math.round(currentProgress / progressMax * 100);
      this.setState({
        totalProgress
      });
      return;
    }

    let totalSize = sizedFiles.reduce((acc, file) => {
      return acc + file.progress.bytesTotal;
    }, 0);
    const averageSize = totalSize / sizedFiles.length;
    totalSize += averageSize * unsizedFiles.length;
    let uploadedSize = 0;
    sizedFiles.forEach(file => {
      uploadedSize += file.progress.bytesUploaded;
    });
    unsizedFiles.forEach(file => {
      uploadedSize += averageSize * (file.progress.percentage || 0) / 100;
    });
    let totalProgress = totalSize === 0 ? 0 : Math.round(uploadedSize / totalSize * 100); // hot fix, because:
    // uploadedSize ended up larger than totalSize, resulting in 1325% total

    if (totalProgress > 100) {
      totalProgress = 100;
    }

    this.setState({
      totalProgress
    });
    this.emit('progress', totalProgress);
  }
  /**
   * Registers listeners for all global actions, like:
   * `error`, `file-removed`, `upload-progress`
   */


  updateOnlineStatus() {
    const online = typeof window.navigator.onLine !== 'undefined' ? window.navigator.onLine : true;

    if (!online) {
      this.emit('is-offline');
      this.info(this.i18n('noInternetConnection'), 'error', 0);
      this.wasOffline = true;
    } else {
      this.emit('is-online');

      if (this.wasOffline) {
        this.emit('back-online');
        this.info(this.i18n('connectedToInternet'), 'success', 3000);
        this.wasOffline = false;
      }
    }
  }

  getID() {
    return this.opts.id;
  }
  /**
   * Registers a plugin with Core.
   *
   * @param {object} Plugin object
   * @param {object} [opts] object with options to be passed to Plugin
   * @returns {object} self for chaining
   */
  // eslint-disable-next-line no-shadow


  use(Plugin, opts) {
    if (typeof Plugin !== 'function') {
      const msg = `Expected a plugin class, but got ${Plugin === null ? 'null' : typeof Plugin}.` + ' Please verify that the plugin was imported and spelled correctly.';
      throw new TypeError(msg);
    } // Instantiate


    const plugin = new Plugin(this, opts);
    const pluginId = plugin.id;

    if (!pluginId) {
      throw new Error('Your plugin must have an id');
    }

    if (!plugin.type) {
      throw new Error('Your plugin must have a type');
    }

    const existsPluginAlready = this.getPlugin(pluginId);

    if (existsPluginAlready) {
      const msg = `Already found a plugin named '${existsPluginAlready.id}'. ` + `Tried to use: '${pluginId}'.\n` + 'Uppy plugins must have unique `id` options. See https://uppy.io/docs/plugins/#id.';
      throw new Error(msg);
    }

    if (Plugin.VERSION) {
      this.log(`Using ${pluginId} v${Plugin.VERSION}`);
    }

    if (plugin.type in _classPrivateFieldLooseBase(this, _plugins)[_plugins]) {
      _classPrivateFieldLooseBase(this, _plugins)[_plugins][plugin.type].push(plugin);
    } else {
      _classPrivateFieldLooseBase(this, _plugins)[_plugins][plugin.type] = [plugin];
    }

    plugin.install();
    return this;
  }
  /**
   * Find one Plugin by name.
   *
   * @param {string} id plugin id
   * @returns {BasePlugin|undefined}
   */


  getPlugin(id) {
    for (const plugins of Object.values(_classPrivateFieldLooseBase(this, _plugins)[_plugins])) {
      const foundPlugin = plugins.find(plugin => plugin.id === id);
      if (foundPlugin != null) return foundPlugin;
    }

    return undefined;
  }

  [_Symbol$for](type) {
    return _classPrivateFieldLooseBase(this, _plugins)[_plugins][type];
  }
  /**
   * Iterate through all `use`d plugins.
   *
   * @param {Function} method that will be run on each plugin
   */


  iteratePlugins(method) {
    Object.values(_classPrivateFieldLooseBase(this, _plugins)[_plugins]).flat(1).forEach(method);
  }
  /**
   * Uninstall and remove a plugin.
   *
   * @param {object} instance The plugin instance to remove.
   */


  removePlugin(instance) {
    this.log(`Removing plugin ${instance.id}`);
    this.emit('plugin-remove', instance);

    if (instance.uninstall) {
      instance.uninstall();
    }

    const list = _classPrivateFieldLooseBase(this, _plugins)[_plugins][instance.type]; // list.indexOf failed here, because Vue3 converted the plugin instance
    // to a Proxy object, which failed the strict comparison test:
    // obj !== objProxy


    const index = list.findIndex(item => item.id === instance.id);

    if (index !== -1) {
      list.splice(index, 1);
    }

    const state = this.getState();
    const updatedState = {
      plugins: { ...state.plugins,
        [instance.id]: undefined
      }
    };
    this.setState(updatedState);
  }
  /**
   * Uninstall all plugins and close down this Uppy instance.
   */


  close(_temp2) {
    let {
      reason
    } = _temp2 === void 0 ? {} : _temp2;
    this.log(`Closing Uppy instance ${this.opts.id}: removing all files and uninstalling plugins`);
    this.cancelAll({
      reason
    });

    _classPrivateFieldLooseBase(this, _storeUnsubscribe)[_storeUnsubscribe]();

    this.iteratePlugins(plugin => {
      this.removePlugin(plugin);
    });

    if (typeof window !== 'undefined' && window.removeEventListener) {
      window.removeEventListener('online', _classPrivateFieldLooseBase(this, _updateOnlineStatus)[_updateOnlineStatus]);
      window.removeEventListener('offline', _classPrivateFieldLooseBase(this, _updateOnlineStatus)[_updateOnlineStatus]);
    }
  }

  hideInfo() {
    const {
      info
    } = this.getState();
    this.setState({
      info: info.slice(1)
    });
    this.emit('info-hidden');
  }
  /**
   * Set info message in `state.info`, so that UI plugins like `Informer`
   * can display the message.
   *
   * @param {string | object} message Message to be displayed by the informer
   * @param {string} [type]
   * @param {number} [duration]
   */


  info(message, type, duration) {
    if (type === void 0) {
      type = 'info';
    }

    if (duration === void 0) {
      duration = 3000;
    }

    const isComplexMessage = typeof message === 'object';
    this.setState({
      info: [...this.getState().info, {
        type,
        message: isComplexMessage ? message.message : message,
        details: isComplexMessage ? message.details : null
      }]
    });
    setTimeout(() => this.hideInfo(), duration);
    this.emit('info-visible');
  }
  /**
   * Passes messages to a function, provided in `opts.logger`.
   * If `opts.logger: Uppy.debugLogger` or `opts.debug: true`, logs to the browser console.
   *
   * @param {string|object} message to log
   * @param {string} [type] optional `error` or `warning`
   */


  log(message, type) {
    const {
      logger
    } = this.opts;

    switch (type) {
      case 'error':
        logger.error(message);
        break;

      case 'warning':
        logger.warn(message);
        break;

      default:
        logger.debug(message);
        break;
    }
  }
  /**
   * Restore an upload by its ID.
   */


  restore(uploadID) {
    this.log(`Core: attempting to restore upload "${uploadID}"`);

    if (!this.getState().currentUploads[uploadID]) {
      _classPrivateFieldLooseBase(this, _removeUpload)[_removeUpload](uploadID);

      return Promise.reject(new Error('Nonexistent upload'));
    }

    return _classPrivateFieldLooseBase(this, _runUpload)[_runUpload](uploadID);
  }
  /**
   * Create an upload for a bunch of files.
   *
   * @param {Array<string>} fileIDs File IDs to include in this upload.
   * @returns {string} ID of this upload.
   */


  [_Symbol$for2]() {
    return _classPrivateFieldLooseBase(this, _createUpload)[_createUpload](...arguments);
  }

  /**
   * Add data to an upload's result object.
   *
   * @param {string} uploadID The ID of the upload.
   * @param {object} data Data properties to add to the result object.
   */
  addResultData(uploadID, data) {
    if (!_classPrivateFieldLooseBase(this, _getUpload)[_getUpload](uploadID)) {
      this.log(`Not setting result for an upload that has been removed: ${uploadID}`);
      return;
    }

    const {
      currentUploads
    } = this.getState();
    const currentUpload = { ...currentUploads[uploadID],
      result: { ...currentUploads[uploadID].result,
        ...data
      }
    };
    this.setState({
      currentUploads: { ...currentUploads,
        [uploadID]: currentUpload
      }
    });
  }
  /**
   * Remove an upload, eg. if it has been canceled or completed.
   *
   * @param {string} uploadID The ID of the upload.
   */


  /**
   * Start an upload for all the files that are not currently being uploaded.
   *
   * @returns {Promise}
   */
  upload() {
    var _classPrivateFieldLoo;

    if (!((_classPrivateFieldLoo = _classPrivateFieldLooseBase(this, _plugins)[_plugins].uploader) != null && _classPrivateFieldLoo.length)) {
      this.log('No uploader type plugins are used', 'warning');
    }

    let {
      files
    } = this.getState();
    const onBeforeUploadResult = this.opts.onBeforeUpload(files);

    if (onBeforeUploadResult === false) {
      return Promise.reject(new Error('Not starting the upload because onBeforeUpload returned false'));
    }

    if (onBeforeUploadResult && typeof onBeforeUploadResult === 'object') {
      files = onBeforeUploadResult; // Updating files in state, because uploader plugins receive file IDs,
      // and then fetch the actual file object from state

      this.setState({
        files
      });
    }

    return Promise.resolve().then(() => _classPrivateFieldLooseBase(this, _restricter)[_restricter].validateMinNumberOfFiles(files)).catch(err => {
      _classPrivateFieldLooseBase(this, _informAndEmit)[_informAndEmit]([err]);

      throw err;
    }).then(() => {
      if (!_classPrivateFieldLooseBase(this, _checkRequiredMetaFields)[_checkRequiredMetaFields](files)) {
        throw new _Restricter_js__WEBPACK_IMPORTED_MODULE_10__.RestrictionError(this.i18n('missingRequiredMetaField'));
      }
    }).catch(err => {
      // Doing this in a separate catch because we already emited and logged
      // all the errors in `checkRequiredMetaFields` so we only throw a generic
      // missing fields error here.
      throw err;
    }).then(() => {
      const {
        currentUploads
      } = this.getState(); // get a list of files that are currently assigned to uploads

      const currentlyUploadingFiles = Object.values(currentUploads).flatMap(curr => curr.fileIDs);
      const waitingFileIDs = [];
      Object.keys(files).forEach(fileID => {
        const file = this.getFile(fileID); // if the file hasn't started uploading and hasn't already been assigned to an upload..

        if (!file.progress.uploadStarted && currentlyUploadingFiles.indexOf(fileID) === -1) {
          waitingFileIDs.push(file.id);
        }
      });

      const uploadID = _classPrivateFieldLooseBase(this, _createUpload)[_createUpload](waitingFileIDs);

      return _classPrivateFieldLooseBase(this, _runUpload)[_runUpload](uploadID);
    }).catch(err => {
      this.emit('error', err);
      this.log(err, 'error');
      throw err;
    });
  }

}

function _informAndEmit2(errors) {
  for (const error of errors) {
    const {
      file,
      isRestriction
    } = error;

    if (isRestriction) {
      this.emit('restriction-failed', file, error);
    } else {
      this.emit('error', error);
    }

    this.log(error, 'warning');
  }

  const userFacingErrors = errors.filter(error => error.isUserFacing); // don't flood the user: only show the first 4 toasts

  const maxNumToShow = 4;
  const firstErrors = userFacingErrors.slice(0, maxNumToShow);
  const additionalErrors = userFacingErrors.slice(maxNumToShow);
  firstErrors.forEach(_ref3 => {
    let {
      message,
      details = ''
    } = _ref3;
    this.info({
      message,
      details
    }, 'error', this.opts.infoTimeout);
  });

  if (additionalErrors.length > 0) {
    this.info({
      message: this.i18n('additionalRestrictionsFailed', {
        count: additionalErrors.length
      })
    });
  }
}

function _checkRequiredMetaFieldsOnFile2(file) {
  const {
    missingFields,
    error
  } = _classPrivateFieldLooseBase(this, _restricter)[_restricter].getMissingRequiredMetaFields(file);

  if (missingFields.length > 0) {
    this.setFileState(file.id, {
      missingRequiredMetaFields: missingFields
    });
    this.log(error.message);
    this.emit('restriction-failed', file, error);
    return false;
  }

  return true;
}

function _checkRequiredMetaFields2(files) {
  let success = true;

  for (const file of Object.values(files)) {
    if (!_classPrivateFieldLooseBase(this, _checkRequiredMetaFieldsOnFile)[_checkRequiredMetaFieldsOnFile](file)) {
      success = false;
    }
  }

  return success;
}

function _assertNewUploadAllowed2(file) {
  const {
    allowNewUpload
  } = this.getState();

  if (allowNewUpload === false) {
    const error = new _Restricter_js__WEBPACK_IMPORTED_MODULE_10__.RestrictionError(this.i18n('noMoreFilesAllowed'), {
      file
    });

    _classPrivateFieldLooseBase(this, _informAndEmit)[_informAndEmit]([error]);

    throw error;
  }
}

function _transformFile2(fileDescriptorOrFile) {
  // Uppy expects files in { name, type, size, data } format.
  // If the actual File object is passed from input[type=file] or drag-drop,
  // we normalize it to match Uppy file object
  const fileDescriptor = fileDescriptorOrFile instanceof File ? {
    name: fileDescriptorOrFile.name,
    type: fileDescriptorOrFile.type,
    size: fileDescriptorOrFile.size,
    data: fileDescriptorOrFile
  } : fileDescriptorOrFile;
  const fileType = (0,_uppy_utils_lib_getFileType__WEBPACK_IMPORTED_MODULE_4__["default"])(fileDescriptor);
  const fileName = (0,_getFileName_js__WEBPACK_IMPORTED_MODULE_8__["default"])(fileType, fileDescriptor);
  const fileExtension = (0,_uppy_utils_lib_getFileNameAndExtension__WEBPACK_IMPORTED_MODULE_5__["default"])(fileName).extension;
  const isRemote = Boolean(fileDescriptor.isRemote);
  const id = (0,_uppy_utils_lib_generateFileID__WEBPACK_IMPORTED_MODULE_6__.getSafeFileId)(fileDescriptor);
  const meta = fileDescriptor.meta || {};
  meta.name = fileName;
  meta.type = fileType; // `null` means the size is unknown.

  const size = Number.isFinite(fileDescriptor.data.size) ? fileDescriptor.data.size : null;
  return {
    source: fileDescriptor.source || '',
    id,
    name: fileName,
    extension: fileExtension || '',
    meta: { ...this.getState().meta,
      ...meta
    },
    type: fileType,
    data: fileDescriptor.data,
    progress: {
      percentage: 0,
      bytesUploaded: 0,
      bytesTotal: size,
      uploadComplete: false,
      uploadStarted: null
    },
    size,
    isRemote,
    remote: fileDescriptor.remote || '',
    preview: fileDescriptor.preview
  };
}

function _startIfAutoProceed2() {
  if (this.opts.autoProceed && !this.scheduledAutoProceed) {
    this.scheduledAutoProceed = setTimeout(() => {
      this.scheduledAutoProceed = null;
      this.upload().catch(err => {
        if (!err.isRestriction) {
          this.log(err.stack || err.message || err);
        }
      });
    }, 4);
  }
}

function _checkAndUpdateFileState2(filesToAdd) {
  const {
    files: existingFiles
  } = this.getState(); // create a copy of the files object only once

  const nextFilesState = { ...existingFiles
  };
  const validFilesToAdd = [];
  const errors = [];

  for (const fileToAdd of filesToAdd) {
    try {
      var _existingFiles$newFil;

      let newFile = _classPrivateFieldLooseBase(this, _transformFile)[_transformFile](fileToAdd); // If a file has been recovered (Golden Retriever), but we were unable to recover its data (probably too large),
      // users are asked to re-select these half-recovered files and then this method will be called again.
      // In order to keep the progress, meta and everthing else, we keep the existing file,
      // but we replace `data`, and we remove `isGhost`, because the file is no longer a ghost now


      if ((_existingFiles$newFil = existingFiles[newFile.id]) != null && _existingFiles$newFil.isGhost) {
        const {
          isGhost,
          ...existingFileState
        } = existingFiles[newFile.id];
        newFile = { ...existingFileState,
          data: fileToAdd.data
        };
        this.log(`Replaced the blob in the restored ghost file: ${newFile.name}, ${newFile.id}`);
      }

      if (this.checkIfFileAlreadyExists(newFile.id)) {
        throw new _Restricter_js__WEBPACK_IMPORTED_MODULE_10__.RestrictionError(this.i18n('noDuplicates', {
          fileName: newFile.name
        }), {
          file: fileToAdd
        });
      }

      const onBeforeFileAddedResult = this.opts.onBeforeFileAdded(newFile, nextFilesState);

      if (onBeforeFileAddedResult === false) {
        // Don’t show UI info for this error, as it should be done by the developer
        throw new _Restricter_js__WEBPACK_IMPORTED_MODULE_10__.RestrictionError('Cannot add the file because onBeforeFileAdded returned false.', {
          isUserFacing: false,
          file: fileToAdd
        });
      } else if (typeof onBeforeFileAddedResult === 'object' && onBeforeFileAddedResult !== null) {
        newFile = onBeforeFileAddedResult;
      }

      _classPrivateFieldLooseBase(this, _restricter)[_restricter].validateSingleFile(newFile); // need to add it to the new local state immediately, so we can use the state to validate the next files too


      nextFilesState[newFile.id] = newFile;
      validFilesToAdd.push(newFile);
    } catch (err) {
      errors.push(err);
    }
  }

  try {
    // need to run this separately because it's much more slow, so if we run it inside the for-loop it will be very slow
    // when many files are added
    _classPrivateFieldLooseBase(this, _restricter)[_restricter].validateAggregateRestrictions(Object.values(existingFiles), validFilesToAdd);
  } catch (err) {
    errors.push(err); // If we have any aggregate error, don't allow adding this batch

    return {
      nextFilesState: existingFiles,
      validFilesToAdd: [],
      errors
    };
  }

  return {
    nextFilesState,
    validFilesToAdd,
    errors
  };
}

function _addListeners2() {
  /**
   * @param {Error} error
   * @param {object} [file]
   * @param {object} [response]
   */
  const errorHandler = (error, file, response) => {
    let errorMsg = error.message || 'Unknown error';

    if (error.details) {
      errorMsg += ` ${error.details}`;
    }

    this.setState({
      error: errorMsg
    });

    if (file != null && file.id in this.getState().files) {
      this.setFileState(file.id, {
        error: errorMsg,
        response
      });
    }
  };

  this.on('error', errorHandler);
  this.on('upload-error', (file, error, response) => {
    errorHandler(error, file, response);

    if (typeof error === 'object' && error.message) {
      const newError = new Error(error.message);
      newError.isUserFacing = true; // todo maybe don't do this with all errors?

      newError.details = error.message;

      if (error.details) {
        newError.details += ` ${error.details}`;
      }

      newError.message = this.i18n('failedToUpload', {
        file: file == null ? void 0 : file.name
      });

      _classPrivateFieldLooseBase(this, _informAndEmit)[_informAndEmit]([newError]);
    } else {
      _classPrivateFieldLooseBase(this, _informAndEmit)[_informAndEmit]([error]);
    }
  });
  let uploadStalledWarningRecentlyEmitted;
  this.on('upload-stalled', (error, files) => {
    const {
      message
    } = error;
    const details = files.map(file => file.meta.name).join(', ');

    if (!uploadStalledWarningRecentlyEmitted) {
      this.info({
        message,
        details
      }, 'warning', this.opts.infoTimeout);
      uploadStalledWarningRecentlyEmitted = setTimeout(() => {
        uploadStalledWarningRecentlyEmitted = null;
      }, this.opts.infoTimeout);
    }

    this.log(`${message} ${details}`.trim(), 'warning');
  });
  this.on('upload', () => {
    this.setState({
      error: null
    });
  });

  const onUploadStarted = files => {
    const filesFiltered = files.filter(file => {
      const exists = file != null && this.getFile(file.id);
      if (!exists) this.log(`Not setting progress for a file that has been removed: ${file == null ? void 0 : file.id}`);
      return exists;
    });
    const filesState = Object.fromEntries(filesFiltered.map(file => [file.id, {
      progress: {
        uploadStarted: Date.now(),
        uploadComplete: false,
        percentage: 0,
        bytesUploaded: 0,
        bytesTotal: file.size
      }
    }]));
    this.patchFilesState(filesState);
  };

  this.on('upload-start', files => {
    files.forEach(file => {
      // todo backward compat, remove this event in a next major
      this.emit('upload-started', file);
    });
    onUploadStarted(files);
  });
  this.on('upload-progress', this.calculateProgress);
  this.on('upload-success', (file, uploadResp) => {
    if (file == null || !this.getFile(file.id)) {
      this.log(`Not setting progress for a file that has been removed: ${file == null ? void 0 : file.id}`);
      return;
    }

    const currentProgress = this.getFile(file.id).progress;
    this.setFileState(file.id, {
      progress: { ...currentProgress,
        postprocess: _classPrivateFieldLooseBase(this, _postProcessors)[_postProcessors].size > 0 ? {
          mode: 'indeterminate'
        } : null,
        uploadComplete: true,
        percentage: 100,
        bytesUploaded: currentProgress.bytesTotal
      },
      response: uploadResp,
      uploadURL: uploadResp.uploadURL,
      isPaused: false
    }); // Remote providers sometimes don't tell us the file size,
    // but we can know how many bytes we uploaded once the upload is complete.

    if (file.size == null) {
      this.setFileState(file.id, {
        size: uploadResp.bytesUploaded || currentProgress.bytesTotal
      });
    }

    this.calculateTotalProgress();
  });
  this.on('preprocess-progress', (file, progress) => {
    if (file == null || !this.getFile(file.id)) {
      this.log(`Not setting progress for a file that has been removed: ${file == null ? void 0 : file.id}`);
      return;
    }

    this.setFileState(file.id, {
      progress: { ...this.getFile(file.id).progress,
        preprocess: progress
      }
    });
  });
  this.on('preprocess-complete', file => {
    if (file == null || !this.getFile(file.id)) {
      this.log(`Not setting progress for a file that has been removed: ${file == null ? void 0 : file.id}`);
      return;
    }

    const files = { ...this.getState().files
    };
    files[file.id] = { ...files[file.id],
      progress: { ...files[file.id].progress
      }
    };
    delete files[file.id].progress.preprocess;
    this.setState({
      files
    });
  });
  this.on('postprocess-progress', (file, progress) => {
    if (file == null || !this.getFile(file.id)) {
      this.log(`Not setting progress for a file that has been removed: ${file == null ? void 0 : file.id}`);
      return;
    }

    this.setFileState(file.id, {
      progress: { ...this.getState().files[file.id].progress,
        postprocess: progress
      }
    });
  });
  this.on('postprocess-complete', file => {
    if (file == null || !this.getFile(file.id)) {
      this.log(`Not setting progress for a file that has been removed: ${file == null ? void 0 : file.id}`);
      return;
    }

    const files = { ...this.getState().files
    };
    files[file.id] = { ...files[file.id],
      progress: { ...files[file.id].progress
      }
    };
    delete files[file.id].progress.postprocess;
    this.setState({
      files
    });
  });
  this.on('restored', () => {
    // Files may have changed--ensure progress is still accurate.
    this.calculateTotalProgress();
  });
  this.on('dashboard:file-edit-complete', file => {
    if (file) {
      _classPrivateFieldLooseBase(this, _checkRequiredMetaFieldsOnFile)[_checkRequiredMetaFieldsOnFile](file);
    }
  }); // show informer if offline

  if (typeof window !== 'undefined' && window.addEventListener) {
    window.addEventListener('online', _classPrivateFieldLooseBase(this, _updateOnlineStatus)[_updateOnlineStatus]);
    window.addEventListener('offline', _classPrivateFieldLooseBase(this, _updateOnlineStatus)[_updateOnlineStatus]);
    setTimeout(_classPrivateFieldLooseBase(this, _updateOnlineStatus)[_updateOnlineStatus], 3000);
  }
}

function _createUpload2(fileIDs, opts) {
  if (opts === void 0) {
    opts = {};
  }

  // uppy.retryAll sets this to true — when retrying we want to ignore `allowNewUpload: false`
  const {
    forceAllowNewUpload = false
  } = opts;
  const {
    allowNewUpload,
    currentUploads
  } = this.getState();

  if (!allowNewUpload && !forceAllowNewUpload) {
    throw new Error('Cannot create a new upload: already uploading.');
  }

  const uploadID = (0,nanoid_non_secure__WEBPACK_IMPORTED_MODULE_12__.nanoid)();
  this.emit('upload', {
    id: uploadID,
    fileIDs
  });
  this.setState({
    allowNewUpload: this.opts.allowMultipleUploadBatches !== false && this.opts.allowMultipleUploads !== false,
    currentUploads: { ...currentUploads,
      [uploadID]: {
        fileIDs,
        step: 0,
        result: {}
      }
    }
  });
  return uploadID;
}

function _getUpload2(uploadID) {
  const {
    currentUploads
  } = this.getState();
  return currentUploads[uploadID];
}

function _removeUpload2(uploadID) {
  const currentUploads = { ...this.getState().currentUploads
  };
  delete currentUploads[uploadID];
  this.setState({
    currentUploads
  });
}

async function _runUpload2(uploadID) {
  const getCurrentUpload = () => {
    const {
      currentUploads
    } = this.getState();
    return currentUploads[uploadID];
  };

  let currentUpload = getCurrentUpload();
  const steps = [..._classPrivateFieldLooseBase(this, _preProcessors)[_preProcessors], ..._classPrivateFieldLooseBase(this, _uploaders)[_uploaders], ..._classPrivateFieldLooseBase(this, _postProcessors)[_postProcessors]];

  try {
    for (let step = currentUpload.step || 0; step < steps.length; step++) {
      if (!currentUpload) {
        break;
      }

      const fn = steps[step];
      this.setState({
        currentUploads: { ...this.getState().currentUploads,
          [uploadID]: { ...currentUpload,
            step
          }
        }
      });
      const {
        fileIDs
      } = currentUpload; // TODO give this the `updatedUpload` object as its only parameter maybe?
      // Otherwise when more metadata may be added to the upload this would keep getting more parameters

      await fn(fileIDs, uploadID); // Update currentUpload value in case it was modified asynchronously.

      currentUpload = getCurrentUpload();
    }
  } catch (err) {
    _classPrivateFieldLooseBase(this, _removeUpload)[_removeUpload](uploadID);

    throw err;
  } // Set result data.


  if (currentUpload) {
    // Mark postprocessing step as complete if necessary; this addresses a case where we might get
    // stuck in the postprocessing UI while the upload is fully complete.
    // If the postprocessing steps do not do any work, they may not emit postprocessing events at
    // all, and never mark the postprocessing as complete. This is fine on its own but we
    // introduced code in the @uppy/core upload-success handler to prepare postprocessing progress
    // state if any postprocessors are registered. That is to avoid a "flash of completed state"
    // before the postprocessing plugins can emit events.
    //
    // So, just in case an upload with postprocessing plugins *has* completed *without* emitting
    // postprocessing completion, we do it instead.
    currentUpload.fileIDs.forEach(fileID => {
      const file = this.getFile(fileID);

      if (file && file.progress.postprocess) {
        this.emit('postprocess-complete', file);
      }
    });
    const files = currentUpload.fileIDs.map(fileID => this.getFile(fileID));
    const successful = files.filter(file => !file.error);
    const failed = files.filter(file => file.error);
    await this.addResultData(uploadID, {
      successful,
      failed,
      uploadID
    }); // Update currentUpload value in case it was modified asynchronously.

    currentUpload = getCurrentUpload();
  } // Emit completion events.
  // This is in a separate function so that the `currentUploads` variable
  // always refers to the latest state. In the handler right above it refers
  // to an outdated object without the `.result` property.


  let result;

  if (currentUpload) {
    result = currentUpload.result;
    this.emit('complete', result);

    _classPrivateFieldLooseBase(this, _removeUpload)[_removeUpload](uploadID);
  }

  if (result == null) {
    this.log(`Not setting result for an upload that has been removed: ${uploadID}`);
  }

  return result;
}

Uppy.VERSION = packageJson.version;
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Uppy);

/***/ }),

/***/ "./node_modules/@uppy/core/lib/getFileName.js":
/*!****************************************************!*\
  !*** ./node_modules/@uppy/core/lib/getFileName.js ***!
  \****************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getFileName)
/* harmony export */ });
function getFileName(fileType, fileDescriptor) {
  if (fileDescriptor.name) {
    return fileDescriptor.name;
  }

  if (fileType.split('/')[0] === 'image') {
    return `${fileType.split('/')[0]}.${fileType.split('/')[1]}`;
  }

  return 'noname';
}

/***/ }),

/***/ "./node_modules/@uppy/core/lib/index.js":
/*!**********************************************!*\
  !*** ./node_modules/@uppy/core/lib/index.js ***!
  \**********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   BasePlugin: () => (/* reexport safe */ _BasePlugin_js__WEBPACK_IMPORTED_MODULE_2__["default"]),
/* harmony export */   UIPlugin: () => (/* reexport safe */ _UIPlugin_js__WEBPACK_IMPORTED_MODULE_1__["default"]),
/* harmony export */   Uppy: () => (/* reexport safe */ _Uppy_js__WEBPACK_IMPORTED_MODULE_0__["default"]),
/* harmony export */   debugLogger: () => (/* reexport safe */ _loggers_js__WEBPACK_IMPORTED_MODULE_3__.debugLogger),
/* harmony export */   "default": () => (/* reexport safe */ _Uppy_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _Uppy_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Uppy.js */ "./node_modules/@uppy/core/lib/Uppy.js");
/* harmony import */ var _UIPlugin_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UIPlugin.js */ "./node_modules/@uppy/core/lib/UIPlugin.js");
/* harmony import */ var _BasePlugin_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./BasePlugin.js */ "./node_modules/@uppy/core/lib/BasePlugin.js");
/* harmony import */ var _loggers_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./loggers.js */ "./node_modules/@uppy/core/lib/loggers.js");






/***/ }),

/***/ "./node_modules/@uppy/core/lib/locale.js":
/*!***********************************************!*\
  !*** ./node_modules/@uppy/core/lib/locale.js ***!
  \***********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  strings: {
    addBulkFilesFailed: {
      0: 'Failed to add %{smart_count} file due to an internal error',
      1: 'Failed to add %{smart_count} files due to internal errors'
    },
    youCanOnlyUploadX: {
      0: 'You can only upload %{smart_count} file',
      1: 'You can only upload %{smart_count} files'
    },
    youHaveToAtLeastSelectX: {
      0: 'You have to select at least %{smart_count} file',
      1: 'You have to select at least %{smart_count} files'
    },
    exceedsSize: '%{file} exceeds maximum allowed size of %{size}',
    missingRequiredMetaField: 'Missing required meta fields',
    missingRequiredMetaFieldOnFile: 'Missing required meta fields in %{fileName}',
    inferiorSize: 'This file is smaller than the allowed size of %{size}',
    youCanOnlyUploadFileTypes: 'You can only upload: %{types}',
    noMoreFilesAllowed: 'Cannot add more files',
    noDuplicates: "Cannot add the duplicate file '%{fileName}', it already exists",
    companionError: 'Connection with Companion failed',
    authAborted: 'Authentication aborted',
    companionUnauthorizeHint: 'To unauthorize to your %{provider} account, please go to %{url}',
    failedToUpload: 'Failed to upload %{file}',
    noInternetConnection: 'No Internet connection',
    connectedToInternet: 'Connected to the Internet',
    // Strings for remote providers
    noFilesFound: 'You have no files or folders here',
    noSearchResults: 'Unfortunately, there are no results for this search',
    selectX: {
      0: 'Select %{smart_count}',
      1: 'Select %{smart_count}'
    },
    allFilesFromFolderNamed: 'All files from folder %{name}',
    openFolderNamed: 'Open folder %{name}',
    cancel: 'Cancel',
    logOut: 'Log out',
    filter: 'Filter',
    resetFilter: 'Reset filter',
    loading: 'Loading...',
    authenticateWithTitle: 'Please authenticate with %{pluginName} to select files',
    authenticateWith: 'Connect to %{pluginName}',
    signInWithGoogle: 'Sign in with Google',
    searchImages: 'Search for images',
    enterTextToSearch: 'Enter text to search for images',
    search: 'Search',
    resetSearch: 'Reset search',
    emptyFolderAdded: 'No files were added from empty folder',
    addedNumFiles: 'Added %{numFiles} file(s)',
    folderAlreadyAdded: 'The folder "%{folder}" was already added',
    folderAdded: {
      0: 'Added %{smart_count} file from %{folder}',
      1: 'Added %{smart_count} files from %{folder}'
    },
    additionalRestrictionsFailed: '%{count} additional restrictions were not fulfilled'
  }
});

/***/ }),

/***/ "./node_modules/@uppy/core/lib/loggers.js":
/*!************************************************!*\
  !*** ./node_modules/@uppy/core/lib/loggers.js ***!
  \************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   debugLogger: () => (/* binding */ debugLogger),
/* harmony export */   justErrorsLogger: () => (/* binding */ justErrorsLogger)
/* harmony export */ });
/* harmony import */ var _uppy_utils_lib_getTimeStamp__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @uppy/utils/lib/getTimeStamp */ "./node_modules/@uppy/utils/lib/getTimeStamp.js");
/* eslint-disable no-console */
 // Swallow all logs, except errors.
// default if logger is not set or debug: false

const justErrorsLogger = {
  debug: () => {},
  warn: () => {},
  error: function () {
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    return console.error(`[Uppy] [${(0,_uppy_utils_lib_getTimeStamp__WEBPACK_IMPORTED_MODULE_0__["default"])()}]`, ...args);
  }
}; // Print logs to console with namespace + timestamp,
// set by logger: Uppy.debugLogger or debug: true

const debugLogger = {
  debug: function () {
    for (var _len2 = arguments.length, args = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
      args[_key2] = arguments[_key2];
    }

    return console.debug(`[Uppy] [${(0,_uppy_utils_lib_getTimeStamp__WEBPACK_IMPORTED_MODULE_0__["default"])()}]`, ...args);
  },
  warn: function () {
    for (var _len3 = arguments.length, args = new Array(_len3), _key3 = 0; _key3 < _len3; _key3++) {
      args[_key3] = arguments[_key3];
    }

    return console.warn(`[Uppy] [${(0,_uppy_utils_lib_getTimeStamp__WEBPACK_IMPORTED_MODULE_0__["default"])()}]`, ...args);
  },
  error: function () {
    for (var _len4 = arguments.length, args = new Array(_len4), _key4 = 0; _key4 < _len4; _key4++) {
      args[_key4] = arguments[_key4];
    }

    return console.error(`[Uppy] [${(0,_uppy_utils_lib_getTimeStamp__WEBPACK_IMPORTED_MODULE_0__["default"])()}]`, ...args);
  }
};


/***/ }),

/***/ "./node_modules/@uppy/core/lib/supportsUploadProgress.js":
/*!***************************************************************!*\
  !*** ./node_modules/@uppy/core/lib/supportsUploadProgress.js ***!
  \***************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ supportsUploadProgress)
/* harmony export */ });
// Edge 15.x does not fire 'progress' events on uploads.
// See https://github.com/transloadit/uppy/issues/945
// And https://developer.microsoft.com/en-us/microsoft-edge/platform/issues/12224510/
function supportsUploadProgress(userAgent) {
  // Allow passing in userAgent for tests
  if (userAgent == null && typeof navigator !== 'undefined') {
    // eslint-disable-next-line no-param-reassign
    userAgent = navigator.userAgent;
  } // Assume it works because basically everything supports progress events.


  if (!userAgent) return true;
  const m = /Edge\/(\d+\.\d+)/.exec(userAgent);
  if (!m) return true;
  const edgeVersion = m[1];
  let [major, minor] = edgeVersion.split('.');
  major = parseInt(major, 10);
  minor = parseInt(minor, 10); // Worked before:
  // Edge 40.15063.0.0
  // Microsoft EdgeHTML 15.15063

  if (major < 15 || major === 15 && minor < 15063) {
    return true;
  } // Fixed in:
  // Microsoft EdgeHTML 18.18218


  if (major > 18 || major === 18 && minor >= 18218) {
    return true;
  } // other versions don't work.


  return false;
}

/***/ }),

/***/ "./node_modules/@uppy/store-default/lib/index.js":
/*!*******************************************************!*\
  !*** ./node_modules/@uppy/store-default/lib/index.js ***!
  \*******************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
function _classPrivateFieldLooseBase(receiver, privateKey) { if (!Object.prototype.hasOwnProperty.call(receiver, privateKey)) { throw new TypeError("attempted to use private field on non-instance"); } return receiver; }

var id = 0;

function _classPrivateFieldLooseKey(name) { return "__private_" + id++ + "_" + name; }

const packageJson = {
  "version": "3.0.3"
};
/**
 * Default store that keeps state in a simple object.
 */

var _callbacks = /*#__PURE__*/_classPrivateFieldLooseKey("callbacks");

var _publish = /*#__PURE__*/_classPrivateFieldLooseKey("publish");

class DefaultStore {
  constructor() {
    Object.defineProperty(this, _publish, {
      value: _publish2
    });
    Object.defineProperty(this, _callbacks, {
      writable: true,
      value: new Set()
    });
    this.state = {};
  }

  getState() {
    return this.state;
  }

  setState(patch) {
    const prevState = { ...this.state
    };
    const nextState = { ...this.state,
      ...patch
    };
    this.state = nextState;

    _classPrivateFieldLooseBase(this, _publish)[_publish](prevState, nextState, patch);
  }

  subscribe(listener) {
    _classPrivateFieldLooseBase(this, _callbacks)[_callbacks].add(listener);

    return () => {
      _classPrivateFieldLooseBase(this, _callbacks)[_callbacks].delete(listener);
    };
  }

}

function _publish2() {
  for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
    args[_key] = arguments[_key];
  }

  _classPrivateFieldLooseBase(this, _callbacks)[_callbacks].forEach(listener => {
    listener(...args);
  });
}

DefaultStore.VERSION = packageJson.version;
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DefaultStore);

/***/ }),

/***/ "./node_modules/@uppy/tus/lib/getFingerprint.js":
/*!******************************************************!*\
  !*** ./node_modules/@uppy/tus/lib/getFingerprint.js ***!
  \******************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getFingerprint)
/* harmony export */ });
/* harmony import */ var tus_js_client__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! tus-js-client */ "./node_modules/tus-js-client/lib.esm/browser/index.js");


function isCordova() {
  return typeof window !== 'undefined' && (typeof window.PhoneGap !== 'undefined' || typeof window.Cordova !== 'undefined' || typeof window.cordova !== 'undefined');
}

function isReactNative() {
  return typeof navigator !== 'undefined' && typeof navigator.product === 'string' && navigator.product.toLowerCase() === 'reactnative';
} // We override tus fingerprint to uppy’s `file.id`, since the `file.id`
// now also includes `relativePath` for files added from folders.
// This means you can add 2 identical files, if one is in folder a,
// the other in folder b — `a/file.jpg` and `b/file.jpg`, when added
// together with a folder, will be treated as 2 separate files.
//
// For React Native and Cordova, we let tus-js-client’s default
// fingerprint handling take charge.


function getFingerprint(uppyFileObj) {
  return (file, options) => {
    if (isCordova() || isReactNative()) {
      return tus_js_client__WEBPACK_IMPORTED_MODULE_0__.defaultOptions.fingerprint(file, options);
    }

    const uppyFingerprint = ['tus', uppyFileObj.id, options.endpoint].join('-');
    return Promise.resolve(uppyFingerprint);
  };
}

/***/ }),

/***/ "./node_modules/@uppy/tus/lib/index.js":
/*!*********************************************!*\
  !*** ./node_modules/@uppy/tus/lib/index.js ***!
  \*********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Tus)
/* harmony export */ });
/* harmony import */ var _uppy_core_lib_BasePlugin_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @uppy/core/lib/BasePlugin.js */ "./node_modules/@uppy/core/lib/BasePlugin.js");
/* harmony import */ var tus_js_client__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! tus-js-client */ "./node_modules/tus-js-client/lib.esm/browser/index.js");
/* harmony import */ var _uppy_companion_client__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @uppy/companion-client */ "./node_modules/@uppy/companion-client/lib/index.js");
/* harmony import */ var _uppy_utils_lib_emitSocketProgress__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @uppy/utils/lib/emitSocketProgress */ "./node_modules/@uppy/utils/lib/emitSocketProgress.js");
/* harmony import */ var _uppy_utils_lib_getSocketHost__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @uppy/utils/lib/getSocketHost */ "./node_modules/@uppy/utils/lib/getSocketHost.js");
/* harmony import */ var _uppy_utils_lib_EventTracker__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @uppy/utils/lib/EventTracker */ "./node_modules/@uppy/utils/lib/EventTracker.js");
/* harmony import */ var _uppy_utils_lib_NetworkError__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @uppy/utils/lib/NetworkError */ "./node_modules/@uppy/utils/lib/NetworkError.js");
/* harmony import */ var _uppy_utils_lib_isNetworkError__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @uppy/utils/lib/isNetworkError */ "./node_modules/@uppy/utils/lib/isNetworkError.js");
/* harmony import */ var _uppy_utils_lib_RateLimitedQueue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @uppy/utils/lib/RateLimitedQueue */ "./node_modules/@uppy/utils/lib/RateLimitedQueue.js");
/* harmony import */ var _uppy_utils_lib_hasProperty__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @uppy/utils/lib/hasProperty */ "./node_modules/@uppy/utils/lib/hasProperty.js");
/* harmony import */ var _uppy_utils_lib_fileFilters__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @uppy/utils/lib/fileFilters */ "./node_modules/@uppy/utils/lib/fileFilters.js");
/* harmony import */ var _getFingerprint_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./getFingerprint.js */ "./node_modules/@uppy/tus/lib/getFingerprint.js");
function _classPrivateFieldLooseBase(receiver, privateKey) { if (!Object.prototype.hasOwnProperty.call(receiver, privateKey)) { throw new TypeError("attempted to use private field on non-instance"); } return receiver; }

var id = 0;

function _classPrivateFieldLooseKey(name) { return "__private_" + id++ + "_" + name; }













const packageJson = {
  "version": "3.1.0"
};
/** @typedef {import('..').TusOptions} TusOptions */

/** @typedef {import('tus-js-client').UploadOptions} RawTusOptions */

/** @typedef {import('@uppy/core').Uppy} Uppy */

/** @typedef {import('@uppy/core').UppyFile} UppyFile */

/** @typedef {import('@uppy/core').FailedUppyFile<{}>} FailedUppyFile */

/**
 * Extracted from https://github.com/tus/tus-js-client/blob/master/lib/upload.js#L13
 * excepted we removed 'fingerprint' key to avoid adding more dependencies
 *
 * @type {RawTusOptions}
 */

const tusDefaultOptions = {
  endpoint: '',
  uploadUrl: null,
  metadata: {},
  uploadSize: null,
  onProgress: null,
  onChunkComplete: null,
  onSuccess: null,
  onError: null,
  overridePatchMethod: false,
  headers: {},
  addRequestId: false,
  chunkSize: Infinity,
  retryDelays: [100, 1000, 3000, 5000],
  parallelUploads: 1,
  removeFingerprintOnSuccess: false,
  uploadLengthDeferred: false,
  uploadDataDuringCreation: false
};
/**
 * Tus resumable file uploader
 */

var _retryDelayIterator = /*#__PURE__*/_classPrivateFieldLooseKey("retryDelayIterator");

var _queueRequestSocketToken = /*#__PURE__*/_classPrivateFieldLooseKey("queueRequestSocketToken");

var _upload = /*#__PURE__*/_classPrivateFieldLooseKey("upload");

var _requestSocketToken = /*#__PURE__*/_classPrivateFieldLooseKey("requestSocketToken");

var _uploadRemote = /*#__PURE__*/_classPrivateFieldLooseKey("uploadRemote");

var _uploadFiles = /*#__PURE__*/_classPrivateFieldLooseKey("uploadFiles");

var _handleUpload = /*#__PURE__*/_classPrivateFieldLooseKey("handleUpload");

class Tus extends _uppy_core_lib_BasePlugin_js__WEBPACK_IMPORTED_MODULE_0__["default"] {
  /**
   * @param {Uppy} uppy
   * @param {TusOptions} opts
   */
  constructor(uppy, _opts) {
    var _this$opts$rateLimite, _this$opts$retryDelay;

    super(uppy, _opts);
    Object.defineProperty(this, _uploadFiles, {
      value: _uploadFiles2
    });
    Object.defineProperty(this, _uploadRemote, {
      value: _uploadRemote2
    });
    Object.defineProperty(this, _upload, {
      value: _upload2
    });
    Object.defineProperty(this, _retryDelayIterator, {
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, _queueRequestSocketToken, {
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, _requestSocketToken, {
      writable: true,
      value: async file => {
        const Client = file.remote.providerOptions.provider ? _uppy_companion_client__WEBPACK_IMPORTED_MODULE_2__.Provider : _uppy_companion_client__WEBPACK_IMPORTED_MODULE_2__.RequestClient;
        const client = new Client(this.uppy, file.remote.providerOptions);
        const opts = { ...this.opts
        };

        if (file.tus) {
          // Install file-specific upload overrides.
          Object.assign(opts, file.tus);
        }

        const res = await client.post(file.remote.url, { ...file.remote.body,
          endpoint: opts.endpoint,
          uploadUrl: opts.uploadUrl,
          protocol: 'tus',
          size: file.data.size,
          headers: opts.headers,
          metadata: file.meta
        });
        return res.token;
      }
    });
    Object.defineProperty(this, _handleUpload, {
      writable: true,
      value: async fileIDs => {
        if (fileIDs.length === 0) {
          this.uppy.log('[Tus] No files to upload');
          return;
        }

        if (this.opts.limit === 0) {
          this.uppy.log('[Tus] When uploading multiple files at once, consider setting the `limit` option (to `10` for example), to limit the number of concurrent uploads, which helps prevent memory and network issues: https://uppy.io/docs/tus/#limit-0', 'warning');
        }

        this.uppy.log('[Tus] Uploading...');
        const filesToUpload = this.uppy.getFilesByIds(fileIDs);
        await _classPrivateFieldLooseBase(this, _uploadFiles)[_uploadFiles](filesToUpload);
      }
    });
    this.type = 'uploader';
    this.id = this.opts.id || 'Tus';
    this.title = 'Tus'; // set default options

    const defaultOptions = {
      useFastRemoteRetry: true,
      limit: 20,
      retryDelays: tusDefaultOptions.retryDelays,
      withCredentials: false
    }; // merge default options with the ones set by user

    /** @type {import("..").TusOptions} */

    this.opts = { ...defaultOptions,
      ..._opts
    };

    if ((_opts == null ? void 0 : _opts.allowedMetaFields) === undefined && 'metaFields' in this.opts) {
      throw new Error('The `metaFields` option has been renamed to `allowedMetaFields`.');
    }

    if ('autoRetry' in _opts) {
      throw new Error('The `autoRetry` option was deprecated and has been removed.');
    }
    /**
     * Simultaneous upload limiting is shared across all uploads with this plugin.
     *
     * @type {RateLimitedQueue}
     */


    this.requests = (_this$opts$rateLimite = this.opts.rateLimitedQueue) != null ? _this$opts$rateLimite : new _uppy_utils_lib_RateLimitedQueue__WEBPACK_IMPORTED_MODULE_8__.RateLimitedQueue(this.opts.limit);
    _classPrivateFieldLooseBase(this, _retryDelayIterator)[_retryDelayIterator] = (_this$opts$retryDelay = this.opts.retryDelays) == null ? void 0 : _this$opts$retryDelay.values();
    this.uploaders = Object.create(null);
    this.uploaderEvents = Object.create(null);
    this.uploaderSockets = Object.create(null);
    this.handleResetProgress = this.handleResetProgress.bind(this);
    _classPrivateFieldLooseBase(this, _queueRequestSocketToken)[_queueRequestSocketToken] = this.requests.wrapPromiseFunction(_classPrivateFieldLooseBase(this, _requestSocketToken)[_requestSocketToken], {
      priority: -1
    });
  }

  handleResetProgress() {
    const files = { ...this.uppy.getState().files
    };
    Object.keys(files).forEach(fileID => {
      // Only clone the file object if it has a Tus `uploadUrl` attached.
      if (files[fileID].tus && files[fileID].tus.uploadUrl) {
        const tusState = { ...files[fileID].tus
        };
        delete tusState.uploadUrl;
        files[fileID] = { ...files[fileID],
          tus: tusState
        };
      }
    });
    this.uppy.setState({
      files
    });
  }
  /**
   * Clean up all references for a file's upload: the tus.Upload instance,
   * any events related to the file, and the Companion WebSocket connection.
   *
   * @param {string} fileID
   */


  resetUploaderReferences(fileID, opts) {
    if (opts === void 0) {
      opts = {};
    }

    if (this.uploaders[fileID]) {
      const uploader = this.uploaders[fileID];
      uploader.abort();

      if (opts.abort) {
        uploader.abort(true);
      }

      this.uploaders[fileID] = null;
    }

    if (this.uploaderEvents[fileID]) {
      this.uploaderEvents[fileID].remove();
      this.uploaderEvents[fileID] = null;
    }

    if (this.uploaderSockets[fileID]) {
      this.uploaderSockets[fileID].close();
      this.uploaderSockets[fileID] = null;
    }
  }
  /**
   * Create a new Tus upload.
   *
   * A lot can happen during an upload, so this is quite hard to follow!
   * - First, the upload is started. If the file was already paused by the time the upload starts, nothing should happen.
   *   If the `limit` option is used, the upload must be queued onto the `this.requests` queue.
   *   When an upload starts, we store the tus.Upload instance, and an EventTracker instance that manages the event listeners
   *   for pausing, cancellation, removal, etc.
   * - While the upload is in progress, it may be paused or cancelled.
   *   Pausing aborts the underlying tus.Upload, and removes the upload from the `this.requests` queue. All other state is
   *   maintained.
   *   Cancelling removes the upload from the `this.requests` queue, and completely aborts the upload-- the `tus.Upload`
   *   instance is aborted and discarded, the EventTracker instance is destroyed (removing all listeners).
   *   Resuming the upload uses the `this.requests` queue as well, to prevent selectively pausing and resuming uploads from
   *   bypassing the limit.
   * - After completing an upload, the tus.Upload and EventTracker instances are cleaned up, and the upload is marked as done
   *   in the `this.requests` queue.
   * - When an upload completed with an error, the same happens as on successful completion, but the `upload()` promise is
   *   rejected.
   *
   * When working on this function, keep in mind:
   *  - When an upload is completed or cancelled for any reason, the tus.Upload and EventTracker instances need to be cleaned
   *    up using this.resetUploaderReferences().
   *  - When an upload is cancelled or paused, for any reason, it needs to be removed from the `this.requests` queue using
   *    `queuedRequest.abort()`.
   *  - When an upload is completed for any reason, including errors, it needs to be marked as such using
   *    `queuedRequest.done()`.
   *  - When an upload is started or resumed, it needs to go through the `this.requests` queue. The `queuedRequest` variable
   *    must be updated so the other uses of it are valid.
   *  - Before replacing the `queuedRequest` variable, the previous `queuedRequest` must be aborted, else it will keep taking
   *    up a spot in the queue.
   *
   * @param {UppyFile} file for use with upload
   * @returns {Promise<void>}
   */


  /**
   * See the comment on the upload() method.
   *
   * Additionally, when an upload is removed, completed, or cancelled, we need to close the WebSocket connection. This is
   * handled by the resetUploaderReferences() function, so the same guidelines apply as in upload().
   *
   * @param {UppyFile} file
   */
  async connectToServerSocket(file) {
    var _this = this;

    return new Promise((resolve, reject) => {
      const token = file.serverToken;
      const host = (0,_uppy_utils_lib_getSocketHost__WEBPACK_IMPORTED_MODULE_4__["default"])(file.remote.companionUrl);
      const socket = new _uppy_companion_client__WEBPACK_IMPORTED_MODULE_2__.Socket({
        target: `${host}/api/${token}`,
        autoOpen: false
      });
      this.uploaderSockets[file.id] = socket;
      this.uploaderEvents[file.id] = new _uppy_utils_lib_EventTracker__WEBPACK_IMPORTED_MODULE_5__["default"](this.uppy);
      let queuedRequest;
      this.onFileRemove(file.id, () => {
        queuedRequest.abort();
        socket.send('cancel', {});
        this.resetUploaderReferences(file.id);
        resolve(`upload ${file.id} was removed`);
      });
      this.onPause(file.id, isPaused => {
        if (isPaused) {
          // Remove this file from the queue so another file can start in its place.
          queuedRequest.abort();
          socket.send('pause', {});
        } else {
          // Resuming an upload should be queued, else you could pause and then
          // resume a queued upload to make it skip the queue.
          queuedRequest.abort();
          queuedRequest = this.requests.run(() => {
            socket.open();
            socket.send('resume', {});
            return () => socket.close();
          });
        }
      });
      this.onPauseAll(file.id, () => {
        queuedRequest.abort();
        socket.send('pause', {});
      });
      this.onCancelAll(file.id, function (_temp) {
        let {
          reason
        } = _temp === void 0 ? {} : _temp;

        if (reason === 'user') {
          queuedRequest.abort();
          socket.send('cancel', {});

          _this.resetUploaderReferences(file.id);
        }

        resolve(`upload ${file.id} was canceled`);
      });
      this.onResumeAll(file.id, () => {
        queuedRequest.abort();

        if (file.error) {
          socket.send('pause', {});
        }

        queuedRequest = this.requests.run(() => {
          socket.open();
          socket.send('resume', {});
          return () => socket.close();
        });
      });
      this.onRetry(file.id, () => {
        // Only do the retry if the upload is actually in progress;
        // else we could try to send these messages when the upload is still queued.
        // We may need a better check for this since the socket may also be closed
        // for other reasons, like network failures.
        if (socket.isOpen) {
          socket.send('pause', {});
          socket.send('resume', {});
        }
      });
      this.onRetryAll(file.id, () => {
        // See the comment in the onRetry() call
        if (socket.isOpen) {
          socket.send('pause', {});
          socket.send('resume', {});
        }
      });
      socket.on('progress', progressData => (0,_uppy_utils_lib_emitSocketProgress__WEBPACK_IMPORTED_MODULE_3__["default"])(this, progressData, file));
      socket.on('error', errData => {
        const {
          message
        } = errData.error;
        const error = Object.assign(new Error(message), {
          cause: errData.error
        }); // If the remote retry optimisation should not be used,
        // close the socket—this will tell companion to clear state and delete the file.

        if (!this.opts.useFastRemoteRetry) {
          this.resetUploaderReferences(file.id); // Remove the serverToken so that a new one will be created for the retry.

          this.uppy.setFileState(file.id, {
            serverToken: null
          });
        } else {
          socket.close();
        }

        this.uppy.emit('upload-error', file, error);
        queuedRequest.done();
        reject(error);
      });
      socket.on('success', data => {
        const uploadResp = {
          uploadURL: data.url
        };
        this.uppy.emit('upload-success', file, uploadResp);
        this.resetUploaderReferences(file.id);
        queuedRequest.done();
        resolve();
      });
      queuedRequest = this.requests.run(() => {
        if (file.isPaused) {
          socket.send('pause', {});
        } else {
          socket.open();
        } // Just close the socket here, the caller will take care of cancelling the upload itself
        // using resetUploaderReferences(). This is because resetUploaderReferences() has to be
        // called when this request is still in the queue, and has not been started yet, too. At
        // that point this cancellation function is not going to be called.
        // Also, we need to remove the request from the queue _without_ destroying everything
        // related to this upload to handle pauses.


        return () => socket.close();
      });
    });
  }
  /**
   * Store the uploadUrl on the file options, so that when Golden Retriever
   * restores state, we will continue uploading to the correct URL.
   *
   * @param {UppyFile} file
   * @param {string} uploadURL
   */


  onReceiveUploadUrl(file, uploadURL) {
    const currentFile = this.uppy.getFile(file.id);
    if (!currentFile) return; // Only do the update if we didn't have an upload URL yet.

    if (!currentFile.tus || currentFile.tus.uploadUrl !== uploadURL) {
      this.uppy.log('[Tus] Storing upload url');
      this.uppy.setFileState(currentFile.id, {
        tus: { ...currentFile.tus,
          uploadUrl: uploadURL
        }
      });
    }
  }
  /**
   * @param {string} fileID
   * @param {function(string): void} cb
   */


  onFileRemove(fileID, cb) {
    this.uploaderEvents[fileID].on('file-removed', file => {
      if (fileID === file.id) cb(file.id);
    });
  }
  /**
   * @param {string} fileID
   * @param {function(boolean): void} cb
   */


  onPause(fileID, cb) {
    this.uploaderEvents[fileID].on('upload-pause', (targetFileID, isPaused) => {
      if (fileID === targetFileID) {
        // const isPaused = this.uppy.pauseResume(fileID)
        cb(isPaused);
      }
    });
  }
  /**
   * @param {string} fileID
   * @param {function(): void} cb
   */


  onRetry(fileID, cb) {
    this.uploaderEvents[fileID].on('upload-retry', targetFileID => {
      if (fileID === targetFileID) {
        cb();
      }
    });
  }
  /**
   * @param {string} fileID
   * @param {function(): void} cb
   */


  onRetryAll(fileID, cb) {
    this.uploaderEvents[fileID].on('retry-all', () => {
      if (!this.uppy.getFile(fileID)) return;
      cb();
    });
  }
  /**
   * @param {string} fileID
   * @param {function(): void} cb
   */


  onPauseAll(fileID, cb) {
    this.uploaderEvents[fileID].on('pause-all', () => {
      if (!this.uppy.getFile(fileID)) return;
      cb();
    });
  }
  /**
   * @param {string} fileID
   * @param {function(): void} eventHandler
   */


  onCancelAll(fileID, eventHandler) {
    var _this2 = this;

    this.uploaderEvents[fileID].on('cancel-all', function () {
      if (!_this2.uppy.getFile(fileID)) return;
      eventHandler(...arguments);
    });
  }
  /**
   * @param {string} fileID
   * @param {function(): void} cb
   */


  onResumeAll(fileID, cb) {
    this.uploaderEvents[fileID].on('resume-all', () => {
      if (!this.uppy.getFile(fileID)) return;
      cb();
    });
  }
  /**
   * @param {(UppyFile | FailedUppyFile)[]} files
   */


  install() {
    this.uppy.setState({
      capabilities: { ...this.uppy.getState().capabilities,
        resumableUploads: true
      }
    });
    this.uppy.addUploader(_classPrivateFieldLooseBase(this, _handleUpload)[_handleUpload]);
    this.uppy.on('reset-progress', this.handleResetProgress);
  }

  uninstall() {
    this.uppy.setState({
      capabilities: { ...this.uppy.getState().capabilities,
        resumableUploads: false
      }
    });
    this.uppy.removeUploader(_classPrivateFieldLooseBase(this, _handleUpload)[_handleUpload]);
  }

}

function _upload2(file) {
  var _this3 = this;

  this.resetUploaderReferences(file.id); // Create a new tus upload

  return new Promise((resolve, reject) => {
    let queuedRequest;
    let qRequest;
    let upload;
    const opts = { ...this.opts,
      ...(file.tus || {})
    };

    if (typeof opts.headers === 'function') {
      opts.headers = opts.headers(file);
    }
    /** @type {RawTusOptions} */


    const uploadOptions = { ...tusDefaultOptions,
      ...opts
    }; // We override tus fingerprint to uppy’s `file.id`, since the `file.id`
    // now also includes `relativePath` for files added from folders.
    // This means you can add 2 identical files, if one is in folder a,
    // the other in folder b.

    uploadOptions.fingerprint = (0,_getFingerprint_js__WEBPACK_IMPORTED_MODULE_11__["default"])(file);

    uploadOptions.onBeforeRequest = req => {
      const xhr = req.getUnderlyingObject();
      xhr.withCredentials = !!opts.withCredentials;
      let userProvidedPromise;

      if (typeof opts.onBeforeRequest === 'function') {
        userProvidedPromise = opts.onBeforeRequest(req, file);
      }

      if ((0,_uppy_utils_lib_hasProperty__WEBPACK_IMPORTED_MODULE_9__["default"])(queuedRequest, 'shouldBeRequeued')) {
        if (!queuedRequest.shouldBeRequeued) return Promise.reject();
        let done;
        const p = new Promise(res => {
          // eslint-disable-line promise/param-names
          done = res;
        });
        queuedRequest = this.requests.run(() => {
          if (file.isPaused) {
            queuedRequest.abort();
          }

          done();
          return () => {};
        }); // If the request has been requeued because it was rate limited by the
        // remote server, we want to wait for `RateLimitedQueue` to dispatch
        // the re-try request.
        // Therefore we create a promise that the queue will resolve when
        // enough time has elapsed to expect not to be rate-limited again.
        // This means we can hold the Tus retry here with a `Promise.all`,
        // together with the returned value of the user provided
        // `onBeforeRequest` option callback (in case it returns a promise).

        return Promise.all([p, userProvidedPromise]);
      }

      return userProvidedPromise;
    };

    uploadOptions.onError = err => {
      var _queuedRequest;

      this.uppy.log(err);
      const xhr = err.originalRequest ? err.originalRequest.getUnderlyingObject() : null;

      if ((0,_uppy_utils_lib_isNetworkError__WEBPACK_IMPORTED_MODULE_7__["default"])(xhr)) {
        // eslint-disable-next-line no-param-reassign
        err = new _uppy_utils_lib_NetworkError__WEBPACK_IMPORTED_MODULE_6__["default"](err, xhr);
      }

      this.resetUploaderReferences(file.id);
      (_queuedRequest = queuedRequest) == null ? void 0 : _queuedRequest.abort();
      this.uppy.emit('upload-error', file, err);
      reject(err);
    };

    uploadOptions.onProgress = (bytesUploaded, bytesTotal) => {
      this.onReceiveUploadUrl(file, upload.url);
      this.uppy.emit('upload-progress', file, {
        uploader: this,
        bytesUploaded,
        bytesTotal
      });
    };

    uploadOptions.onSuccess = () => {
      const uploadResp = {
        uploadURL: upload.url
      };
      this.resetUploaderReferences(file.id);
      queuedRequest.done();
      this.uppy.emit('upload-success', file, uploadResp);

      if (upload.url) {
        this.uppy.log(`Download ${upload.file.name} from ${upload.url}`);
      }

      resolve(upload);
    };

    const defaultOnShouldRetry = err => {
      var _err$originalResponse;

      const status = err == null ? void 0 : (_err$originalResponse = err.originalResponse) == null ? void 0 : _err$originalResponse.getStatus();

      if (status === 429) {
        // HTTP 429 Too Many Requests => to avoid the whole download to fail, pause all requests.
        if (!this.requests.isPaused) {
          var _classPrivateFieldLoo;

          const next = (_classPrivateFieldLoo = _classPrivateFieldLooseBase(this, _retryDelayIterator)[_retryDelayIterator]) == null ? void 0 : _classPrivateFieldLoo.next();

          if (next == null || next.done) {
            return false;
          }

          this.requests.rateLimit(next.value);
        }
      } else if (status > 400 && status < 500 && status !== 409) {
        // HTTP 4xx, the server won't send anything, it's doesn't make sense to retry
        return false;
      } else if (typeof navigator !== 'undefined' && navigator.onLine === false) {
        // The navigator is offline, let's wait for it to come back online.
        if (!this.requests.isPaused) {
          this.requests.pause();
          window.addEventListener('online', () => {
            this.requests.resume();
          }, {
            once: true
          });
        }
      }

      queuedRequest.abort();
      queuedRequest = {
        shouldBeRequeued: true,

        abort() {
          this.shouldBeRequeued = false;
        },

        done() {
          throw new Error('Cannot mark a queued request as done: this indicates a bug');
        },

        fn() {
          throw new Error('Cannot run a queued request: this indicates a bug');
        }

      };
      return true;
    };

    if (opts.onShouldRetry != null) {
      uploadOptions.onShouldRetry = function () {
        for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
          args[_key] = arguments[_key];
        }

        return opts.onShouldRetry(...args, defaultOnShouldRetry);
      };
    } else {
      uploadOptions.onShouldRetry = defaultOnShouldRetry;
    }

    const copyProp = (obj, srcProp, destProp) => {
      if ((0,_uppy_utils_lib_hasProperty__WEBPACK_IMPORTED_MODULE_9__["default"])(obj, srcProp) && !(0,_uppy_utils_lib_hasProperty__WEBPACK_IMPORTED_MODULE_9__["default"])(obj, destProp)) {
        // eslint-disable-next-line no-param-reassign
        obj[destProp] = obj[srcProp];
      }
    };
    /** @type {Record<string, string>} */


    const meta = {};
    const allowedMetaFields = Array.isArray(opts.allowedMetaFields) ? opts.allowedMetaFields // Send along all fields by default.
    : Object.keys(file.meta);
    allowedMetaFields.forEach(item => {
      meta[item] = file.meta[item];
    }); // tusd uses metadata fields 'filetype' and 'filename'

    copyProp(meta, 'type', 'filetype');
    copyProp(meta, 'name', 'filename');
    uploadOptions.metadata = meta;
    upload = new tus_js_client__WEBPACK_IMPORTED_MODULE_1__.Upload(file.data, uploadOptions);
    this.uploaders[file.id] = upload;
    this.uploaderEvents[file.id] = new _uppy_utils_lib_EventTracker__WEBPACK_IMPORTED_MODULE_5__["default"](this.uppy); // eslint-disable-next-line prefer-const

    qRequest = () => {
      if (!file.isPaused) {
        upload.start();
      } // Don't do anything here, the caller will take care of cancelling the upload itself
      // using resetUploaderReferences(). This is because resetUploaderReferences() has to be
      // called when this request is still in the queue, and has not been started yet, too. At
      // that point this cancellation function is not going to be called.
      // Also, we need to remove the request from the queue _without_ destroying everything
      // related to this upload to handle pauses.


      return () => {};
    };

    upload.findPreviousUploads().then(previousUploads => {
      const previousUpload = previousUploads[0];

      if (previousUpload) {
        this.uppy.log(`[Tus] Resuming upload of ${file.id} started at ${previousUpload.creationTime}`);
        upload.resumeFromPreviousUpload(previousUpload);
      }
    });
    queuedRequest = this.requests.run(qRequest);
    this.onFileRemove(file.id, targetFileID => {
      queuedRequest.abort();
      this.resetUploaderReferences(file.id, {
        abort: !!upload.url
      });
      resolve(`upload ${targetFileID} was removed`);
    });
    this.onPause(file.id, isPaused => {
      queuedRequest.abort();

      if (isPaused) {
        // Remove this file from the queue so another file can start in its place.
        upload.abort();
      } else {
        // Resuming an upload should be queued, else you could pause and then
        // resume a queued upload to make it skip the queue.
        queuedRequest = this.requests.run(qRequest);
      }
    });
    this.onPauseAll(file.id, () => {
      queuedRequest.abort();
      upload.abort();
    });
    this.onCancelAll(file.id, function (_temp2) {
      let {
        reason
      } = _temp2 === void 0 ? {} : _temp2;

      if (reason === 'user') {
        queuedRequest.abort();

        _this3.resetUploaderReferences(file.id, {
          abort: !!upload.url
        });
      }

      resolve(`upload ${file.id} was canceled`);
    });
    this.onResumeAll(file.id, () => {
      queuedRequest.abort();

      if (file.error) {
        upload.abort();
      }

      queuedRequest = this.requests.run(qRequest);
    });
  }).catch(err => {
    this.uppy.emit('upload-error', file, err);
    throw err;
  });
}

async function _uploadRemote2(file) {
  this.resetUploaderReferences(file.id);

  try {
    if (file.serverToken) {
      return await this.connectToServerSocket(file);
    }

    const serverToken = await _classPrivateFieldLooseBase(this, _queueRequestSocketToken)[_queueRequestSocketToken](file);
    if (!this.uppy.getState().files[file.id]) return undefined;
    this.uppy.setFileState(file.id, {
      serverToken
    });
    return await this.connectToServerSocket(this.uppy.getFile(file.id));
  } catch (err) {
    this.uppy.setFileState(file.id, {
      serverToken: undefined
    });
    this.uppy.emit('upload-error', file, err);
    throw err;
  }
}

async function _uploadFiles2(files) {
  const filesFiltered = (0,_uppy_utils_lib_fileFilters__WEBPACK_IMPORTED_MODULE_10__.filterNonFailedFiles)(files);
  const filesToEmit = (0,_uppy_utils_lib_fileFilters__WEBPACK_IMPORTED_MODULE_10__.filterFilesToEmitUploadStarted)(filesFiltered);
  this.uppy.emit('upload-start', filesToEmit);
  await Promise.allSettled(filesFiltered.map((file, i) => {
    const current = i + 1;
    const total = files.length;

    if (file.isRemote) {
      return _classPrivateFieldLooseBase(this, _uploadRemote)[_uploadRemote](file, current, total);
    }

    return _classPrivateFieldLooseBase(this, _upload)[_upload](file, current, total);
  }));
}

Tus.VERSION = packageJson.version;

/***/ }),

/***/ "./node_modules/@uppy/utils/lib/ErrorWithCause.js":
/*!********************************************************!*\
  !*** ./node_modules/@uppy/utils/lib/ErrorWithCause.js ***!
  \********************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _hasProperty_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./hasProperty.js */ "./node_modules/@uppy/utils/lib/hasProperty.js");


class ErrorWithCause extends Error {
  constructor(message, options) {
    if (options === void 0) {
      options = {};
    }

    super(message);
    this.cause = options.cause;

    if (this.cause && (0,_hasProperty_js__WEBPACK_IMPORTED_MODULE_0__["default"])(this.cause, 'isNetworkError')) {
      this.isNetworkError = this.cause.isNetworkError;
    }
  }

}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ErrorWithCause);

/***/ }),

/***/ "./node_modules/@uppy/utils/lib/EventTracker.js":
/*!******************************************************!*\
  !*** ./node_modules/@uppy/utils/lib/EventTracker.js ***!
  \******************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ EventTracker)
/* harmony export */ });
function _classPrivateFieldLooseBase(receiver, privateKey) { if (!Object.prototype.hasOwnProperty.call(receiver, privateKey)) { throw new TypeError("attempted to use private field on non-instance"); } return receiver; }

var id = 0;

function _classPrivateFieldLooseKey(name) { return "__private_" + id++ + "_" + name; }

var _emitter = /*#__PURE__*/_classPrivateFieldLooseKey("emitter");

var _events = /*#__PURE__*/_classPrivateFieldLooseKey("events");

/**
 * Create a wrapper around an event emitter with a `remove` method to remove
 * all events that were added using the wrapped emitter.
 */
class EventTracker {
  constructor(emitter) {
    Object.defineProperty(this, _emitter, {
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, _events, {
      writable: true,
      value: []
    });
    _classPrivateFieldLooseBase(this, _emitter)[_emitter] = emitter;
  }

  on(event, fn) {
    _classPrivateFieldLooseBase(this, _events)[_events].push([event, fn]);

    return _classPrivateFieldLooseBase(this, _emitter)[_emitter].on(event, fn);
  }

  remove() {
    for (const [event, fn] of _classPrivateFieldLooseBase(this, _events)[_events].splice(0)) {
      _classPrivateFieldLooseBase(this, _emitter)[_emitter].off(event, fn);
    }
  }

}

/***/ }),

/***/ "./node_modules/@uppy/utils/lib/NetworkError.js":
/*!******************************************************!*\
  !*** ./node_modules/@uppy/utils/lib/NetworkError.js ***!
  \******************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
class NetworkError extends Error {
  constructor(error, xhr) {
    if (xhr === void 0) {
      xhr = null;
    }

    super(`This looks like a network error, the endpoint might be blocked by an internet provider or a firewall.`);
    this.cause = error;
    this.isNetworkError = true;
    this.request = xhr;
  }

}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (NetworkError);

/***/ }),

/***/ "./node_modules/@uppy/utils/lib/RateLimitedQueue.js":
/*!**********************************************************!*\
  !*** ./node_modules/@uppy/utils/lib/RateLimitedQueue.js ***!
  \**********************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   RateLimitedQueue: () => (/* binding */ RateLimitedQueue),
/* harmony export */   internalRateLimitedQueue: () => (/* binding */ internalRateLimitedQueue)
/* harmony export */ });
function _classPrivateFieldLooseBase(receiver, privateKey) { if (!Object.prototype.hasOwnProperty.call(receiver, privateKey)) { throw new TypeError("attempted to use private field on non-instance"); } return receiver; }

var id = 0;

function _classPrivateFieldLooseKey(name) { return "__private_" + id++ + "_" + name; }

function createCancelError(cause) {
  return new Error('Cancelled', {
    cause
  });
}

function abortOn(signal) {
  if (signal != null) {
    const abortPromise = () => this.abort(signal.reason);

    signal.addEventListener('abort', abortPromise, {
      once: true
    });

    const removeAbortListener = () => {
      signal.removeEventListener('abort', abortPromise);
    };

    this.then(removeAbortListener, removeAbortListener);
  }

  return this;
}

var _activeRequests = /*#__PURE__*/_classPrivateFieldLooseKey("activeRequests");

var _queuedHandlers = /*#__PURE__*/_classPrivateFieldLooseKey("queuedHandlers");

var _paused = /*#__PURE__*/_classPrivateFieldLooseKey("paused");

var _pauseTimer = /*#__PURE__*/_classPrivateFieldLooseKey("pauseTimer");

var _downLimit = /*#__PURE__*/_classPrivateFieldLooseKey("downLimit");

var _upperLimit = /*#__PURE__*/_classPrivateFieldLooseKey("upperLimit");

var _rateLimitingTimer = /*#__PURE__*/_classPrivateFieldLooseKey("rateLimitingTimer");

var _call = /*#__PURE__*/_classPrivateFieldLooseKey("call");

var _queueNext = /*#__PURE__*/_classPrivateFieldLooseKey("queueNext");

var _next = /*#__PURE__*/_classPrivateFieldLooseKey("next");

var _queue = /*#__PURE__*/_classPrivateFieldLooseKey("queue");

var _dequeue = /*#__PURE__*/_classPrivateFieldLooseKey("dequeue");

var _resume = /*#__PURE__*/_classPrivateFieldLooseKey("resume");

var _increaseLimit = /*#__PURE__*/_classPrivateFieldLooseKey("increaseLimit");

class RateLimitedQueue {
  constructor(limit) {
    Object.defineProperty(this, _dequeue, {
      value: _dequeue2
    });
    Object.defineProperty(this, _queue, {
      value: _queue2
    });
    Object.defineProperty(this, _next, {
      value: _next2
    });
    Object.defineProperty(this, _queueNext, {
      value: _queueNext2
    });
    Object.defineProperty(this, _call, {
      value: _call2
    });
    Object.defineProperty(this, _activeRequests, {
      writable: true,
      value: 0
    });
    Object.defineProperty(this, _queuedHandlers, {
      writable: true,
      value: []
    });
    Object.defineProperty(this, _paused, {
      writable: true,
      value: false
    });
    Object.defineProperty(this, _pauseTimer, {
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, _downLimit, {
      writable: true,
      value: 1
    });
    Object.defineProperty(this, _upperLimit, {
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, _rateLimitingTimer, {
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, _resume, {
      writable: true,
      value: () => this.resume()
    });
    Object.defineProperty(this, _increaseLimit, {
      writable: true,
      value: () => {
        if (_classPrivateFieldLooseBase(this, _paused)[_paused]) {
          _classPrivateFieldLooseBase(this, _rateLimitingTimer)[_rateLimitingTimer] = setTimeout(_classPrivateFieldLooseBase(this, _increaseLimit)[_increaseLimit], 0);
          return;
        }

        _classPrivateFieldLooseBase(this, _downLimit)[_downLimit] = this.limit;
        this.limit = Math.ceil((_classPrivateFieldLooseBase(this, _upperLimit)[_upperLimit] + _classPrivateFieldLooseBase(this, _downLimit)[_downLimit]) / 2);

        for (let i = _classPrivateFieldLooseBase(this, _downLimit)[_downLimit]; i <= this.limit; i++) {
          _classPrivateFieldLooseBase(this, _queueNext)[_queueNext]();
        }

        if (_classPrivateFieldLooseBase(this, _upperLimit)[_upperLimit] - _classPrivateFieldLooseBase(this, _downLimit)[_downLimit] > 3) {
          _classPrivateFieldLooseBase(this, _rateLimitingTimer)[_rateLimitingTimer] = setTimeout(_classPrivateFieldLooseBase(this, _increaseLimit)[_increaseLimit], 2000);
        } else {
          _classPrivateFieldLooseBase(this, _downLimit)[_downLimit] = Math.floor(_classPrivateFieldLooseBase(this, _downLimit)[_downLimit] / 2);
        }
      }
    });

    if (typeof limit !== 'number' || limit === 0) {
      this.limit = Infinity;
    } else {
      this.limit = limit;
    }
  }

  run(fn, queueOptions) {
    if (!_classPrivateFieldLooseBase(this, _paused)[_paused] && _classPrivateFieldLooseBase(this, _activeRequests)[_activeRequests] < this.limit) {
      return _classPrivateFieldLooseBase(this, _call)[_call](fn);
    }

    return _classPrivateFieldLooseBase(this, _queue)[_queue](fn, queueOptions);
  }

  wrapPromiseFunction(fn, queueOptions) {
    var _this = this;

    return function () {
      for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
        args[_key] = arguments[_key];
      }

      let queuedRequest;
      const outerPromise = new Promise((resolve, reject) => {
        queuedRequest = _this.run(() => {
          let cancelError;
          let innerPromise;

          try {
            innerPromise = Promise.resolve(fn(...args));
          } catch (err) {
            innerPromise = Promise.reject(err);
          }

          innerPromise.then(result => {
            if (cancelError) {
              reject(cancelError);
            } else {
              queuedRequest.done();
              resolve(result);
            }
          }, err => {
            if (cancelError) {
              reject(cancelError);
            } else {
              queuedRequest.done();
              reject(err);
            }
          });
          return cause => {
            cancelError = createCancelError(cause);
          };
        }, queueOptions);
      });

      outerPromise.abort = cause => {
        queuedRequest.abort(cause);
      };

      outerPromise.abortOn = abortOn;
      return outerPromise;
    };
  }

  resume() {
    _classPrivateFieldLooseBase(this, _paused)[_paused] = false;
    clearTimeout(_classPrivateFieldLooseBase(this, _pauseTimer)[_pauseTimer]);

    for (let i = 0; i < this.limit; i++) {
      _classPrivateFieldLooseBase(this, _queueNext)[_queueNext]();
    }
  }

  /**
   * Freezes the queue for a while or indefinitely.
   *
   * @param {number | null } [duration] Duration for the pause to happen, in milliseconds.
   *                                    If omitted, the queue won't resume automatically.
   */
  pause(duration) {
    if (duration === void 0) {
      duration = null;
    }

    _classPrivateFieldLooseBase(this, _paused)[_paused] = true;
    clearTimeout(_classPrivateFieldLooseBase(this, _pauseTimer)[_pauseTimer]);

    if (duration != null) {
      _classPrivateFieldLooseBase(this, _pauseTimer)[_pauseTimer] = setTimeout(_classPrivateFieldLooseBase(this, _resume)[_resume], duration);
    }
  }
  /**
   * Pauses the queue for a duration, and lower the limit of concurrent requests
   * when the queue resumes. When the queue resumes, it tries to progressively
   * increase the limit in `this.#increaseLimit` until another call is made to
   * `this.rateLimit`.
   * Call this function when using the RateLimitedQueue for network requests and
   * the remote server responds with 429 HTTP code.
   *
   * @param {number} duration in milliseconds.
   */


  rateLimit(duration) {
    clearTimeout(_classPrivateFieldLooseBase(this, _rateLimitingTimer)[_rateLimitingTimer]);
    this.pause(duration);

    if (this.limit > 1 && Number.isFinite(this.limit)) {
      _classPrivateFieldLooseBase(this, _upperLimit)[_upperLimit] = this.limit - 1;
      this.limit = _classPrivateFieldLooseBase(this, _downLimit)[_downLimit];
      _classPrivateFieldLooseBase(this, _rateLimitingTimer)[_rateLimitingTimer] = setTimeout(_classPrivateFieldLooseBase(this, _increaseLimit)[_increaseLimit], duration);
    }
  }

  get isPaused() {
    return _classPrivateFieldLooseBase(this, _paused)[_paused];
  }

}

function _call2(fn) {
  _classPrivateFieldLooseBase(this, _activeRequests)[_activeRequests] += 1;
  let done = false;
  let cancelActive;

  try {
    cancelActive = fn();
  } catch (err) {
    _classPrivateFieldLooseBase(this, _activeRequests)[_activeRequests] -= 1;
    throw err;
  }

  return {
    abort: cause => {
      if (done) return;
      done = true;
      _classPrivateFieldLooseBase(this, _activeRequests)[_activeRequests] -= 1;
      cancelActive(cause);

      _classPrivateFieldLooseBase(this, _queueNext)[_queueNext]();
    },
    done: () => {
      if (done) return;
      done = true;
      _classPrivateFieldLooseBase(this, _activeRequests)[_activeRequests] -= 1;

      _classPrivateFieldLooseBase(this, _queueNext)[_queueNext]();
    }
  };
}

function _queueNext2() {
  // Do it soon but not immediately, this allows clearing out the entire queue synchronously
  // one by one without continuously _advancing_ it (and starting new tasks before immediately
  // aborting them)
  queueMicrotask(() => _classPrivateFieldLooseBase(this, _next)[_next]());
}

function _next2() {
  if (_classPrivateFieldLooseBase(this, _paused)[_paused] || _classPrivateFieldLooseBase(this, _activeRequests)[_activeRequests] >= this.limit) {
    return;
  }

  if (_classPrivateFieldLooseBase(this, _queuedHandlers)[_queuedHandlers].length === 0) {
    return;
  } // Dispatch the next request, and update the abort/done handlers
  // so that cancelling it does the Right Thing (and doesn't just try
  // to dequeue an already-running request).


  const next = _classPrivateFieldLooseBase(this, _queuedHandlers)[_queuedHandlers].shift();

  const handler = _classPrivateFieldLooseBase(this, _call)[_call](next.fn);

  next.abort = handler.abort;
  next.done = handler.done;
}

function _queue2(fn, options) {
  if (options === void 0) {
    options = {};
  }

  const handler = {
    fn,
    priority: options.priority || 0,
    abort: () => {
      _classPrivateFieldLooseBase(this, _dequeue)[_dequeue](handler);
    },
    done: () => {
      throw new Error('Cannot mark a queued request as done: this indicates a bug');
    }
  };

  const index = _classPrivateFieldLooseBase(this, _queuedHandlers)[_queuedHandlers].findIndex(other => {
    return handler.priority > other.priority;
  });

  if (index === -1) {
    _classPrivateFieldLooseBase(this, _queuedHandlers)[_queuedHandlers].push(handler);
  } else {
    _classPrivateFieldLooseBase(this, _queuedHandlers)[_queuedHandlers].splice(index, 0, handler);
  }

  return handler;
}

function _dequeue2(handler) {
  const index = _classPrivateFieldLooseBase(this, _queuedHandlers)[_queuedHandlers].indexOf(handler);

  if (index !== -1) {
    _classPrivateFieldLooseBase(this, _queuedHandlers)[_queuedHandlers].splice(index, 1);
  }
}

const internalRateLimitedQueue = Symbol('__queue');

/***/ }),

/***/ "./node_modules/@uppy/utils/lib/Translator.js":
/*!****************************************************!*\
  !*** ./node_modules/@uppy/utils/lib/Translator.js ***!
  \****************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Translator)
/* harmony export */ });
/* harmony import */ var _hasProperty_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./hasProperty.js */ "./node_modules/@uppy/utils/lib/hasProperty.js");
function _classPrivateFieldLooseBase(receiver, privateKey) { if (!Object.prototype.hasOwnProperty.call(receiver, privateKey)) { throw new TypeError("attempted to use private field on non-instance"); } return receiver; }

var id = 0;

function _classPrivateFieldLooseKey(name) { return "__private_" + id++ + "_" + name; }



function insertReplacement(source, rx, replacement) {
  const newParts = [];
  source.forEach(chunk => {
    // When the source contains multiple placeholders for interpolation,
    // we should ignore chunks that are not strings, because those
    // can be JSX objects and will be otherwise incorrectly turned into strings.
    // Without this condition we’d get this: [object Object] hello [object Object] my <button>
    if (typeof chunk !== 'string') {
      return newParts.push(chunk);
    }

    return rx[Symbol.split](chunk).forEach((raw, i, list) => {
      if (raw !== '') {
        newParts.push(raw);
      } // Interlace with the `replacement` value


      if (i < list.length - 1) {
        newParts.push(replacement);
      }
    });
  });
  return newParts;
}
/**
 * Takes a string with placeholder variables like `%{smart_count} file selected`
 * and replaces it with values from options `{smart_count: 5}`
 *
 * @license https://github.com/airbnb/polyglot.js/blob/master/LICENSE
 * taken from https://github.com/airbnb/polyglot.js/blob/master/lib/polyglot.js#L299
 *
 * @param {string} phrase that needs interpolation, with placeholders
 * @param {object} options with values that will be used to replace placeholders
 * @returns {any[]} interpolated
 */


function interpolate(phrase, options) {
  const dollarRegex = /\$/g;
  const dollarBillsYall = '$$$$';
  let interpolated = [phrase];
  if (options == null) return interpolated;

  for (const arg of Object.keys(options)) {
    if (arg !== '_') {
      // Ensure replacement value is escaped to prevent special $-prefixed
      // regex replace tokens. the "$$$$" is needed because each "$" needs to
      // be escaped with "$" itself, and we need two in the resulting output.
      let replacement = options[arg];

      if (typeof replacement === 'string') {
        replacement = dollarRegex[Symbol.replace](replacement, dollarBillsYall);
      } // We create a new `RegExp` each time instead of using a more-efficient
      // string replace so that the same argument can be replaced multiple times
      // in the same phrase.


      interpolated = insertReplacement(interpolated, new RegExp(`%\\{${arg}\\}`, 'g'), replacement);
    }
  }

  return interpolated;
}
/**
 * Translates strings with interpolation & pluralization support.
 * Extensible with custom dictionaries and pluralization functions.
 *
 * Borrows heavily from and inspired by Polyglot https://github.com/airbnb/polyglot.js,
 * basically a stripped-down version of it. Differences: pluralization functions are not hardcoded
 * and can be easily added among with dictionaries, nested objects are used for pluralization
 * as opposed to `||||` delimeter
 *
 * Usage example: `translator.translate('files_chosen', {smart_count: 3})`
 */


var _apply = /*#__PURE__*/_classPrivateFieldLooseKey("apply");

class Translator {
  /**
   * @param {object|Array<object>} locales - locale or list of locales.
   */
  constructor(locales) {
    Object.defineProperty(this, _apply, {
      value: _apply2
    });
    this.locale = {
      strings: {},

      pluralize(n) {
        if (n === 1) {
          return 0;
        }

        return 1;
      }

    };

    if (Array.isArray(locales)) {
      locales.forEach(_classPrivateFieldLooseBase(this, _apply)[_apply], this);
    } else {
      _classPrivateFieldLooseBase(this, _apply)[_apply](locales);
    }
  }

  /**
   * Public translate method
   *
   * @param {string} key
   * @param {object} options with values that will be used later to replace placeholders in string
   * @returns {string} translated (and interpolated)
   */
  translate(key, options) {
    return this.translateArray(key, options).join('');
  }
  /**
   * Get a translation and return the translated and interpolated parts as an array.
   *
   * @param {string} key
   * @param {object} options with values that will be used to replace placeholders
   * @returns {Array} The translated and interpolated parts, in order.
   */


  translateArray(key, options) {
    if (!(0,_hasProperty_js__WEBPACK_IMPORTED_MODULE_0__["default"])(this.locale.strings, key)) {
      throw new Error(`missing string: ${key}`);
    }

    const string = this.locale.strings[key];
    const hasPluralForms = typeof string === 'object';

    if (hasPluralForms) {
      if (options && typeof options.smart_count !== 'undefined') {
        const plural = this.locale.pluralize(options.smart_count);
        return interpolate(string[plural], options);
      }

      throw new Error('Attempted to use a string with plural forms, but no value was given for %{smart_count}');
    }

    return interpolate(string, options);
  }

}

function _apply2(locale) {
  if (!(locale != null && locale.strings)) {
    return;
  }

  const prevLocale = this.locale;
  this.locale = { ...prevLocale,
    strings: { ...prevLocale.strings,
      ...locale.strings
    }
  };
  this.locale.pluralize = locale.pluralize || prevLocale.pluralize;
}

/***/ }),

/***/ "./node_modules/@uppy/utils/lib/emitSocketProgress.js":
/*!************************************************************!*\
  !*** ./node_modules/@uppy/utils/lib/emitSocketProgress.js ***!
  \************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var lodash_throttle__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! lodash.throttle */ "./node_modules/lodash.throttle/index.js");


function emitSocketProgress(uploader, progressData, file) {
  const {
    progress,
    bytesUploaded,
    bytesTotal
  } = progressData;

  if (progress) {
    uploader.uppy.log(`Upload progress: ${progress}`);
    uploader.uppy.emit('upload-progress', file, {
      uploader,
      bytesUploaded,
      bytesTotal
    });
  }
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (lodash_throttle__WEBPACK_IMPORTED_MODULE_0__(emitSocketProgress, 300, {
  leading: true,
  trailing: true
}));

/***/ }),

/***/ "./node_modules/@uppy/utils/lib/fetchWithNetworkError.js":
/*!***************************************************************!*\
  !*** ./node_modules/@uppy/utils/lib/fetchWithNetworkError.js ***!
  \***************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ fetchWithNetworkError)
/* harmony export */ });
/* harmony import */ var _NetworkError_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./NetworkError.js */ "./node_modules/@uppy/utils/lib/NetworkError.js");

/**
 * Wrapper around window.fetch that throws a NetworkError when appropriate
 */

function fetchWithNetworkError() {
  return fetch(...arguments).catch(err => {
    if (err.name === 'AbortError') {
      throw err;
    } else {
      throw new _NetworkError_js__WEBPACK_IMPORTED_MODULE_0__["default"](err);
    }
  });
}

/***/ }),

/***/ "./node_modules/@uppy/utils/lib/fileFilters.js":
/*!*****************************************************!*\
  !*** ./node_modules/@uppy/utils/lib/fileFilters.js ***!
  \*****************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   filterFilesToEmitUploadStarted: () => (/* binding */ filterFilesToEmitUploadStarted),
/* harmony export */   filterNonFailedFiles: () => (/* binding */ filterNonFailedFiles)
/* harmony export */ });
function filterNonFailedFiles(files) {
  const hasError = file => 'error' in file && file.error;

  return files.filter(file => !hasError(file));
} // Don't double-emit upload-started for Golden Retriever-restored files that were already started

function filterFilesToEmitUploadStarted(files) {
  return files.filter(file => !file.progress.uploadStarted || !file.isRestored);
}

/***/ }),

/***/ "./node_modules/@uppy/utils/lib/findDOMElement.js":
/*!********************************************************!*\
  !*** ./node_modules/@uppy/utils/lib/findDOMElement.js ***!
  \********************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ findDOMElement)
/* harmony export */ });
/* harmony import */ var _isDOMElement_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./isDOMElement.js */ "./node_modules/@uppy/utils/lib/isDOMElement.js");

/**
 * Find a DOM element.
 *
 * @param {Node|string} element
 * @returns {Node|null}
 */

function findDOMElement(element, context) {
  if (context === void 0) {
    context = document;
  }

  if (typeof element === 'string') {
    return context.querySelector(element);
  }

  if ((0,_isDOMElement_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element)) {
    return element;
  }

  return null;
}

/***/ }),

/***/ "./node_modules/@uppy/utils/lib/generateFileID.js":
/*!********************************************************!*\
  !*** ./node_modules/@uppy/utils/lib/generateFileID.js ***!
  \********************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ generateFileID),
/* harmony export */   getSafeFileId: () => (/* binding */ getSafeFileId)
/* harmony export */ });
/* harmony import */ var _getFileType_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getFileType.js */ "./node_modules/@uppy/utils/lib/getFileType.js");


function encodeCharacter(character) {
  return character.charCodeAt(0).toString(32);
}

function encodeFilename(name) {
  let suffix = '';
  return name.replace(/[^A-Z0-9]/ig, character => {
    suffix += `-${encodeCharacter(character)}`;
    return '/';
  }) + suffix;
}
/**
 * Takes a file object and turns it into fileID, by converting file.name to lowercase,
 * removing extra characters and adding type, size and lastModified
 *
 * @param {object} file
 * @returns {string} the fileID
 */


function generateFileID(file) {
  // It's tempting to do `[items].filter(Boolean).join('-')` here, but that
  // is slower! simple string concatenation is fast
  let id = 'uppy';

  if (typeof file.name === 'string') {
    id += `-${encodeFilename(file.name.toLowerCase())}`;
  }

  if (file.type !== undefined) {
    id += `-${file.type}`;
  }

  if (file.meta && typeof file.meta.relativePath === 'string') {
    id += `-${encodeFilename(file.meta.relativePath.toLowerCase())}`;
  }

  if (file.data.size !== undefined) {
    id += `-${file.data.size}`;
  }

  if (file.data.lastModified !== undefined) {
    id += `-${file.data.lastModified}`;
  }

  return id;
} // If the provider has a stable, unique ID, then we can use that to identify the file.
// Then we don't have to generate our own ID, and we can add the same file many times if needed (different path)

function hasFileStableId(file) {
  if (!file.isRemote || !file.remote) return false; // These are the providers that it seems like have stable IDs for their files. The other's I haven't checked yet.

  const stableIdProviders = new Set(['box', 'dropbox', 'drive', 'facebook', 'unsplash']);
  return stableIdProviders.has(file.remote.provider);
}

function getSafeFileId(file) {
  if (hasFileStableId(file)) return file.id;
  const fileType = (0,_getFileType_js__WEBPACK_IMPORTED_MODULE_0__["default"])(file);
  return generateFileID({ ...file,
    type: fileType
  });
}

/***/ }),

/***/ "./node_modules/@uppy/utils/lib/getFileNameAndExtension.js":
/*!*****************************************************************!*\
  !*** ./node_modules/@uppy/utils/lib/getFileNameAndExtension.js ***!
  \*****************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getFileNameAndExtension)
/* harmony export */ });
/**
 * Takes a full filename string and returns an object {name, extension}
 *
 * @param {string} fullFileName
 * @returns {object} {name, extension}
 */
function getFileNameAndExtension(fullFileName) {
  const lastDot = fullFileName.lastIndexOf('.'); // these count as no extension: "no-dot", "trailing-dot."

  if (lastDot === -1 || lastDot === fullFileName.length - 1) {
    return {
      name: fullFileName,
      extension: undefined
    };
  }

  return {
    name: fullFileName.slice(0, lastDot),
    extension: fullFileName.slice(lastDot + 1)
  };
}

/***/ }),

/***/ "./node_modules/@uppy/utils/lib/getFileType.js":
/*!*****************************************************!*\
  !*** ./node_modules/@uppy/utils/lib/getFileType.js ***!
  \*****************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getFileType)
/* harmony export */ });
/* harmony import */ var _getFileNameAndExtension_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getFileNameAndExtension.js */ "./node_modules/@uppy/utils/lib/getFileNameAndExtension.js");
/* harmony import */ var _mimeTypes_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./mimeTypes.js */ "./node_modules/@uppy/utils/lib/mimeTypes.js");


function getFileType(file) {
  var _getFileNameAndExtens;

  if (file.type) return file.type;
  const fileExtension = file.name ? (_getFileNameAndExtens = (0,_getFileNameAndExtension_js__WEBPACK_IMPORTED_MODULE_0__["default"])(file.name).extension) == null ? void 0 : _getFileNameAndExtens.toLowerCase() : null;

  if (fileExtension && fileExtension in _mimeTypes_js__WEBPACK_IMPORTED_MODULE_1__["default"]) {
    // else, see if we can map extension to a mime type
    return _mimeTypes_js__WEBPACK_IMPORTED_MODULE_1__["default"][fileExtension];
  } // if all fails, fall back to a generic byte stream type


  return 'application/octet-stream';
}

/***/ }),

/***/ "./node_modules/@uppy/utils/lib/getSocketHost.js":
/*!*******************************************************!*\
  !*** ./node_modules/@uppy/utils/lib/getSocketHost.js ***!
  \*******************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getSocketHost)
/* harmony export */ });
function getSocketHost(url) {
  // get the host domain
  const regex = /^(?:https?:\/\/|\/\/)?(?:[^@\n]+@)?(?:www\.)?([^\n]+)/i;
  const host = regex.exec(url)[1];
  const socketProtocol = /^http:\/\//i.test(url) ? 'ws' : 'wss';
  return `${socketProtocol}://${host}`;
}

/***/ }),

/***/ "./node_modules/@uppy/utils/lib/getTextDirection.js":
/*!**********************************************************!*\
  !*** ./node_modules/@uppy/utils/lib/getTextDirection.js ***!
  \**********************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * Get the declared text direction for an element.
 *
 * @param {Node} element
 * @returns {string|undefined}
 */
function getTextDirection(element) {
  var _element;

  // There is another way to determine text direction using getComputedStyle(), as done here:
  // https://github.com/pencil-js/text-direction/blob/2a235ce95089b3185acec3b51313cbba921b3811/text-direction.js
  //
  // We do not use that approach because we are interested specifically in the _declared_ text direction.
  // If no text direction is declared, we have to provide our own explicit text direction so our
  // bidirectional CSS style sheets work.
  while (element && !element.dir) {
    // eslint-disable-next-line no-param-reassign
    element = element.parentNode;
  }

  return (_element = element) == null ? void 0 : _element.dir;
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (getTextDirection);

/***/ }),

/***/ "./node_modules/@uppy/utils/lib/getTimeStamp.js":
/*!******************************************************!*\
  !*** ./node_modules/@uppy/utils/lib/getTimeStamp.js ***!
  \******************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getTimeStamp)
/* harmony export */ });
/**
 * Adds zero to strings shorter than two characters.
 *
 * @param {number} number
 * @returns {string}
 */
function pad(number) {
  return number < 10 ? `0${number}` : number.toString();
}
/**
 * Returns a timestamp in the format of `hours:minutes:seconds`
 */


function getTimeStamp() {
  const date = new Date();
  const hours = pad(date.getHours());
  const minutes = pad(date.getMinutes());
  const seconds = pad(date.getSeconds());
  return `${hours}:${minutes}:${seconds}`;
}

/***/ }),

/***/ "./node_modules/@uppy/utils/lib/hasProperty.js":
/*!*****************************************************!*\
  !*** ./node_modules/@uppy/utils/lib/hasProperty.js ***!
  \*****************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ has)
/* harmony export */ });
function has(object, key) {
  return Object.prototype.hasOwnProperty.call(object, key);
}

/***/ }),

/***/ "./node_modules/@uppy/utils/lib/isDOMElement.js":
/*!******************************************************!*\
  !*** ./node_modules/@uppy/utils/lib/isDOMElement.js ***!
  \******************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ isDOMElement)
/* harmony export */ });
/**
 * Check if an object is a DOM element. Duck-typing based on `nodeType`.
 *
 * @param {*} obj
 */
function isDOMElement(obj) {
  return (obj == null ? void 0 : obj.nodeType) === Node.ELEMENT_NODE;
}

/***/ }),

/***/ "./node_modules/@uppy/utils/lib/isNetworkError.js":
/*!********************************************************!*\
  !*** ./node_modules/@uppy/utils/lib/isNetworkError.js ***!
  \********************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
function isNetworkError(xhr) {
  if (!xhr) {
    return false;
  }

  return xhr.readyState !== 0 && xhr.readyState !== 4 || xhr.status === 0;
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (isNetworkError);

/***/ }),

/***/ "./node_modules/@uppy/utils/lib/mimeTypes.js":
/*!***************************************************!*\
  !*** ./node_modules/@uppy/utils/lib/mimeTypes.js ***!
  \***************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
// ___Why not add the mime-types package?
//    It's 19.7kB gzipped, and we only need mime types for well-known extensions (for file previews).
// ___Where to take new extensions from?
//    https://github.com/jshttp/mime-db/blob/master/db.json
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  md: 'text/markdown',
  markdown: 'text/markdown',
  mp4: 'video/mp4',
  mp3: 'audio/mp3',
  svg: 'image/svg+xml',
  jpg: 'image/jpeg',
  png: 'image/png',
  webp: 'image/webp',
  gif: 'image/gif',
  heic: 'image/heic',
  heif: 'image/heif',
  yaml: 'text/yaml',
  yml: 'text/yaml',
  csv: 'text/csv',
  tsv: 'text/tab-separated-values',
  tab: 'text/tab-separated-values',
  avi: 'video/x-msvideo',
  mks: 'video/x-matroska',
  mkv: 'video/x-matroska',
  mov: 'video/quicktime',
  dicom: 'application/dicom',
  doc: 'application/msword',
  docm: 'application/vnd.ms-word.document.macroenabled.12',
  docx: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  dot: 'application/msword',
  dotm: 'application/vnd.ms-word.template.macroenabled.12',
  dotx: 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
  xla: 'application/vnd.ms-excel',
  xlam: 'application/vnd.ms-excel.addin.macroenabled.12',
  xlc: 'application/vnd.ms-excel',
  xlf: 'application/x-xliff+xml',
  xlm: 'application/vnd.ms-excel',
  xls: 'application/vnd.ms-excel',
  xlsb: 'application/vnd.ms-excel.sheet.binary.macroenabled.12',
  xlsm: 'application/vnd.ms-excel.sheet.macroenabled.12',
  xlsx: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  xlt: 'application/vnd.ms-excel',
  xltm: 'application/vnd.ms-excel.template.macroenabled.12',
  xltx: 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
  xlw: 'application/vnd.ms-excel',
  txt: 'text/plain',
  text: 'text/plain',
  conf: 'text/plain',
  log: 'text/plain',
  pdf: 'application/pdf',
  zip: 'application/zip',
  '7z': 'application/x-7z-compressed',
  rar: 'application/x-rar-compressed',
  tar: 'application/x-tar',
  gz: 'application/gzip',
  dmg: 'application/x-apple-diskimage'
});

/***/ }),

/***/ "./node_modules/js-base64/base64.mjs":
/*!*******************************************!*\
  !*** ./node_modules/js-base64/base64.mjs ***!
  \*******************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Base64: () => (/* binding */ gBase64),
/* harmony export */   VERSION: () => (/* binding */ VERSION),
/* harmony export */   atob: () => (/* binding */ _atob),
/* harmony export */   atobPolyfill: () => (/* binding */ atobPolyfill),
/* harmony export */   btoa: () => (/* binding */ _btoa),
/* harmony export */   btoaPolyfill: () => (/* binding */ btoaPolyfill),
/* harmony export */   btou: () => (/* binding */ btou),
/* harmony export */   decode: () => (/* binding */ decode),
/* harmony export */   encode: () => (/* binding */ encode),
/* harmony export */   encodeURI: () => (/* binding */ encodeURI),
/* harmony export */   encodeURL: () => (/* binding */ encodeURI),
/* harmony export */   extendBuiltins: () => (/* binding */ extendBuiltins),
/* harmony export */   extendString: () => (/* binding */ extendString),
/* harmony export */   extendUint8Array: () => (/* binding */ extendUint8Array),
/* harmony export */   fromBase64: () => (/* binding */ decode),
/* harmony export */   fromUint8Array: () => (/* binding */ fromUint8Array),
/* harmony export */   isValid: () => (/* binding */ isValid),
/* harmony export */   toBase64: () => (/* binding */ encode),
/* harmony export */   toUint8Array: () => (/* binding */ toUint8Array),
/* harmony export */   utob: () => (/* binding */ utob),
/* harmony export */   version: () => (/* binding */ version)
/* harmony export */ });
/**
 *  base64.ts
 *
 *  Licensed under the BSD 3-Clause License.
 *    http://opensource.org/licenses/BSD-3-Clause
 *
 *  References:
 *    http://en.wikipedia.org/wiki/Base64
 *
 * @author Dan Kogai (https://github.com/dankogai)
 */
const version = '3.7.5';
/**
 * @deprecated use lowercase `version`.
 */
const VERSION = version;
const _hasatob = typeof atob === 'function';
const _hasbtoa = typeof btoa === 'function';
const _hasBuffer = typeof Buffer === 'function';
const _TD = typeof TextDecoder === 'function' ? new TextDecoder() : undefined;
const _TE = typeof TextEncoder === 'function' ? new TextEncoder() : undefined;
const b64ch = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
const b64chs = Array.prototype.slice.call(b64ch);
const b64tab = ((a) => {
    let tab = {};
    a.forEach((c, i) => tab[c] = i);
    return tab;
})(b64chs);
const b64re = /^(?:[A-Za-z\d+\/]{4})*?(?:[A-Za-z\d+\/]{2}(?:==)?|[A-Za-z\d+\/]{3}=?)?$/;
const _fromCC = String.fromCharCode.bind(String);
const _U8Afrom = typeof Uint8Array.from === 'function'
    ? Uint8Array.from.bind(Uint8Array)
    : (it) => new Uint8Array(Array.prototype.slice.call(it, 0));
const _mkUriSafe = (src) => src
    .replace(/=/g, '').replace(/[+\/]/g, (m0) => m0 == '+' ? '-' : '_');
const _tidyB64 = (s) => s.replace(/[^A-Za-z0-9\+\/]/g, '');
/**
 * polyfill version of `btoa`
 */
const btoaPolyfill = (bin) => {
    // console.log('polyfilled');
    let u32, c0, c1, c2, asc = '';
    const pad = bin.length % 3;
    for (let i = 0; i < bin.length;) {
        if ((c0 = bin.charCodeAt(i++)) > 255 ||
            (c1 = bin.charCodeAt(i++)) > 255 ||
            (c2 = bin.charCodeAt(i++)) > 255)
            throw new TypeError('invalid character found');
        u32 = (c0 << 16) | (c1 << 8) | c2;
        asc += b64chs[u32 >> 18 & 63]
            + b64chs[u32 >> 12 & 63]
            + b64chs[u32 >> 6 & 63]
            + b64chs[u32 & 63];
    }
    return pad ? asc.slice(0, pad - 3) + "===".substring(pad) : asc;
};
/**
 * does what `window.btoa` of web browsers do.
 * @param {String} bin binary string
 * @returns {string} Base64-encoded string
 */
const _btoa = _hasbtoa ? (bin) => btoa(bin)
    : _hasBuffer ? (bin) => Buffer.from(bin, 'binary').toString('base64')
        : btoaPolyfill;
const _fromUint8Array = _hasBuffer
    ? (u8a) => Buffer.from(u8a).toString('base64')
    : (u8a) => {
        // cf. https://stackoverflow.com/questions/12710001/how-to-convert-uint8-array-to-base64-encoded-string/12713326#12713326
        const maxargs = 0x1000;
        let strs = [];
        for (let i = 0, l = u8a.length; i < l; i += maxargs) {
            strs.push(_fromCC.apply(null, u8a.subarray(i, i + maxargs)));
        }
        return _btoa(strs.join(''));
    };
/**
 * converts a Uint8Array to a Base64 string.
 * @param {boolean} [urlsafe] URL-and-filename-safe a la RFC4648 §5
 * @returns {string} Base64 string
 */
const fromUint8Array = (u8a, urlsafe = false) => urlsafe ? _mkUriSafe(_fromUint8Array(u8a)) : _fromUint8Array(u8a);
// This trick is found broken https://github.com/dankogai/js-base64/issues/130
// const utob = (src: string) => unescape(encodeURIComponent(src));
// reverting good old fationed regexp
const cb_utob = (c) => {
    if (c.length < 2) {
        var cc = c.charCodeAt(0);
        return cc < 0x80 ? c
            : cc < 0x800 ? (_fromCC(0xc0 | (cc >>> 6))
                + _fromCC(0x80 | (cc & 0x3f)))
                : (_fromCC(0xe0 | ((cc >>> 12) & 0x0f))
                    + _fromCC(0x80 | ((cc >>> 6) & 0x3f))
                    + _fromCC(0x80 | (cc & 0x3f)));
    }
    else {
        var cc = 0x10000
            + (c.charCodeAt(0) - 0xD800) * 0x400
            + (c.charCodeAt(1) - 0xDC00);
        return (_fromCC(0xf0 | ((cc >>> 18) & 0x07))
            + _fromCC(0x80 | ((cc >>> 12) & 0x3f))
            + _fromCC(0x80 | ((cc >>> 6) & 0x3f))
            + _fromCC(0x80 | (cc & 0x3f)));
    }
};
const re_utob = /[\uD800-\uDBFF][\uDC00-\uDFFFF]|[^\x00-\x7F]/g;
/**
 * @deprecated should have been internal use only.
 * @param {string} src UTF-8 string
 * @returns {string} UTF-16 string
 */
const utob = (u) => u.replace(re_utob, cb_utob);
//
const _encode = _hasBuffer
    ? (s) => Buffer.from(s, 'utf8').toString('base64')
    : _TE
        ? (s) => _fromUint8Array(_TE.encode(s))
        : (s) => _btoa(utob(s));
/**
 * converts a UTF-8-encoded string to a Base64 string.
 * @param {boolean} [urlsafe] if `true` make the result URL-safe
 * @returns {string} Base64 string
 */
const encode = (src, urlsafe = false) => urlsafe
    ? _mkUriSafe(_encode(src))
    : _encode(src);
/**
 * converts a UTF-8-encoded string to URL-safe Base64 RFC4648 §5.
 * @returns {string} Base64 string
 */
const encodeURI = (src) => encode(src, true);
// This trick is found broken https://github.com/dankogai/js-base64/issues/130
// const btou = (src: string) => decodeURIComponent(escape(src));
// reverting good old fationed regexp
const re_btou = /[\xC0-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF]{2}|[\xF0-\xF7][\x80-\xBF]{3}/g;
const cb_btou = (cccc) => {
    switch (cccc.length) {
        case 4:
            var cp = ((0x07 & cccc.charCodeAt(0)) << 18)
                | ((0x3f & cccc.charCodeAt(1)) << 12)
                | ((0x3f & cccc.charCodeAt(2)) << 6)
                | (0x3f & cccc.charCodeAt(3)), offset = cp - 0x10000;
            return (_fromCC((offset >>> 10) + 0xD800)
                + _fromCC((offset & 0x3FF) + 0xDC00));
        case 3:
            return _fromCC(((0x0f & cccc.charCodeAt(0)) << 12)
                | ((0x3f & cccc.charCodeAt(1)) << 6)
                | (0x3f & cccc.charCodeAt(2)));
        default:
            return _fromCC(((0x1f & cccc.charCodeAt(0)) << 6)
                | (0x3f & cccc.charCodeAt(1)));
    }
};
/**
 * @deprecated should have been internal use only.
 * @param {string} src UTF-16 string
 * @returns {string} UTF-8 string
 */
const btou = (b) => b.replace(re_btou, cb_btou);
/**
 * polyfill version of `atob`
 */
const atobPolyfill = (asc) => {
    // console.log('polyfilled');
    asc = asc.replace(/\s+/g, '');
    if (!b64re.test(asc))
        throw new TypeError('malformed base64.');
    asc += '=='.slice(2 - (asc.length & 3));
    let u24, bin = '', r1, r2;
    for (let i = 0; i < asc.length;) {
        u24 = b64tab[asc.charAt(i++)] << 18
            | b64tab[asc.charAt(i++)] << 12
            | (r1 = b64tab[asc.charAt(i++)]) << 6
            | (r2 = b64tab[asc.charAt(i++)]);
        bin += r1 === 64 ? _fromCC(u24 >> 16 & 255)
            : r2 === 64 ? _fromCC(u24 >> 16 & 255, u24 >> 8 & 255)
                : _fromCC(u24 >> 16 & 255, u24 >> 8 & 255, u24 & 255);
    }
    return bin;
};
/**
 * does what `window.atob` of web browsers do.
 * @param {String} asc Base64-encoded string
 * @returns {string} binary string
 */
const _atob = _hasatob ? (asc) => atob(_tidyB64(asc))
    : _hasBuffer ? (asc) => Buffer.from(asc, 'base64').toString('binary')
        : atobPolyfill;
//
const _toUint8Array = _hasBuffer
    ? (a) => _U8Afrom(Buffer.from(a, 'base64'))
    : (a) => _U8Afrom(_atob(a).split('').map(c => c.charCodeAt(0)));
/**
 * converts a Base64 string to a Uint8Array.
 */
const toUint8Array = (a) => _toUint8Array(_unURI(a));
//
const _decode = _hasBuffer
    ? (a) => Buffer.from(a, 'base64').toString('utf8')
    : _TD
        ? (a) => _TD.decode(_toUint8Array(a))
        : (a) => btou(_atob(a));
const _unURI = (a) => _tidyB64(a.replace(/[-_]/g, (m0) => m0 == '-' ? '+' : '/'));
/**
 * converts a Base64 string to a UTF-8 string.
 * @param {String} src Base64 string.  Both normal and URL-safe are supported
 * @returns {string} UTF-8 string
 */
const decode = (src) => _decode(_unURI(src));
/**
 * check if a value is a valid Base64 string
 * @param {String} src a value to check
  */
const isValid = (src) => {
    if (typeof src !== 'string')
        return false;
    const s = src.replace(/\s+/g, '').replace(/={0,2}$/, '');
    return !/[^\s0-9a-zA-Z\+/]/.test(s) || !/[^\s0-9a-zA-Z\-_]/.test(s);
};
//
const _noEnum = (v) => {
    return {
        value: v, enumerable: false, writable: true, configurable: true
    };
};
/**
 * extend String.prototype with relevant methods
 */
const extendString = function () {
    const _add = (name, body) => Object.defineProperty(String.prototype, name, _noEnum(body));
    _add('fromBase64', function () { return decode(this); });
    _add('toBase64', function (urlsafe) { return encode(this, urlsafe); });
    _add('toBase64URI', function () { return encode(this, true); });
    _add('toBase64URL', function () { return encode(this, true); });
    _add('toUint8Array', function () { return toUint8Array(this); });
};
/**
 * extend Uint8Array.prototype with relevant methods
 */
const extendUint8Array = function () {
    const _add = (name, body) => Object.defineProperty(Uint8Array.prototype, name, _noEnum(body));
    _add('toBase64', function (urlsafe) { return fromUint8Array(this, urlsafe); });
    _add('toBase64URI', function () { return fromUint8Array(this, true); });
    _add('toBase64URL', function () { return fromUint8Array(this, true); });
};
/**
 * extend Builtin prototypes with relevant methods
 */
const extendBuiltins = () => {
    extendString();
    extendUint8Array();
};
const gBase64 = {
    version: version,
    VERSION: VERSION,
    atob: _atob,
    atobPolyfill: atobPolyfill,
    btoa: _btoa,
    btoaPolyfill: btoaPolyfill,
    fromBase64: decode,
    toBase64: encode,
    encode: encode,
    encodeURI: encodeURI,
    encodeURL: encodeURI,
    utob: utob,
    btou: btou,
    decode: decode,
    isValid: isValid,
    fromUint8Array: fromUint8Array,
    toUint8Array: toUint8Array,
    extendString: extendString,
    extendUint8Array: extendUint8Array,
    extendBuiltins: extendBuiltins,
};
// makecjs:CUT //




















// and finally,



/***/ }),

/***/ "./node_modules/nanoid/non-secure/index.js":
/*!*************************************************!*\
  !*** ./node_modules/nanoid/non-secure/index.js ***!
  \*************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   customAlphabet: () => (/* binding */ customAlphabet),
/* harmony export */   nanoid: () => (/* binding */ nanoid)
/* harmony export */ });
let urlAlphabet =
  'useandom-26T198340PX75pxJACKVERYMINDBUSHWOLF_GQZbfghjklqvwyzrict'
let customAlphabet = (alphabet, defaultSize = 21) => {
  return (size = defaultSize) => {
    let id = ''
    let i = size
    while (i--) {
      id += alphabet[(Math.random() * alphabet.length) | 0]
    }
    return id
  }
}
let nanoid = (size = 21) => {
  let id = ''
  let i = size
  while (i--) {
    id += urlAlphabet[(Math.random() * 64) | 0]
  }
  return id
}


/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/core/lib/BasePlugin.js":
/*!*******************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/core/lib/BasePlugin.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ BasePlugin)
/* harmony export */ });
/* harmony import */ var _uppy_utils_lib_Translator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @uppy/utils/lib/Translator */ "./src/jgDashboard/node_modules/@uppy/utils/lib/Translator.js");
/**
 * Core plugin logic that all plugins share.
 *
 * BasePlugin does not contain DOM rendering so it can be used for plugins
 * without a user interface.
 *
 * See `Plugin` for the extended version with Preact rendering for interfaces.
 */

class BasePlugin {
  constructor(uppy, opts) {
    if (opts === void 0) {
      opts = {};
    }

    this.uppy = uppy;
    this.opts = opts;
  }

  getPluginState() {
    const {
      plugins
    } = this.uppy.getState();
    return plugins[this.id] || {};
  }

  setPluginState(update) {
    const {
      plugins
    } = this.uppy.getState();
    this.uppy.setState({
      plugins: { ...plugins,
        [this.id]: { ...plugins[this.id],
          ...update
        }
      }
    });
  }

  setOptions(newOpts) {
    this.opts = { ...this.opts,
      ...newOpts
    };
    this.setPluginState(); // so that UI re-renders with new options

    this.i18nInit();
  }

  i18nInit() {
    const translator = new _uppy_utils_lib_Translator__WEBPACK_IMPORTED_MODULE_0__["default"]([this.defaultLocale, this.uppy.locale, this.opts.locale]);
    this.i18n = translator.translate.bind(translator);
    this.i18nArray = translator.translateArray.bind(translator);
    this.setPluginState(); // so that UI re-renders and we see the updated locale
  }
  /**
   * Extendable methods
   * ==================
   * These methods are here to serve as an overview of the extendable methods as well as
   * making them not conditional in use, such as `if (this.afterUpdate)`.
   */
  // eslint-disable-next-line class-methods-use-this


  addTarget() {
    throw new Error('Extend the addTarget method to add your plugin to another plugin\'s target');
  } // eslint-disable-next-line class-methods-use-this


  install() {} // eslint-disable-next-line class-methods-use-this


  uninstall() {}
  /**
   * Called when plugin is mounted, whether in DOM or into another plugin.
   * Needed because sometimes plugins are mounted separately/after `install`,
   * so this.el and this.parent might not be available in `install`.
   * This is the case with @uppy/react plugins, for example.
   */


  render() {
    throw new Error('Extend the render method to add your plugin to a DOM element');
  } // eslint-disable-next-line class-methods-use-this


  update() {} // Called after every state update, after everything's mounted. Debounced.
  // eslint-disable-next-line class-methods-use-this


  afterUpdate() {}

}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/core/lib/Restricter.js":
/*!*******************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/core/lib/Restricter.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Restricter: () => (/* binding */ Restricter),
/* harmony export */   RestrictionError: () => (/* binding */ RestrictionError),
/* harmony export */   defaultOptions: () => (/* binding */ defaultOptions)
/* harmony export */ });
/* harmony import */ var _transloadit_prettier_bytes__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @transloadit/prettier-bytes */ "./src/jgDashboard/node_modules/@uppy/core/node_modules/@transloadit/prettier-bytes/prettierBytes.js");
/* harmony import */ var mime_match__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! mime-match */ "./src/jgDashboard/node_modules/mime-match/index.js");
/* eslint-disable max-classes-per-file, class-methods-use-this */


const defaultOptions = {
  maxFileSize: null,
  minFileSize: null,
  maxTotalFileSize: null,
  maxNumberOfFiles: null,
  minNumberOfFiles: null,
  allowedFileTypes: null,
  requiredMetaFields: []
};

class RestrictionError extends Error {
  constructor(message, _temp) {
    let {
      isUserFacing = true,
      file
    } = _temp === void 0 ? {} : _temp;
    super(message);
    this.isRestriction = true;
    this.isUserFacing = isUserFacing;
    if (file != null) this.file = file; // only some restriction errors are related to a particular file
  }

}

class Restricter {
  constructor(getOpts, i18n) {
    this.i18n = i18n;

    this.getOpts = () => {
      const opts = getOpts();

      if (opts.restrictions.allowedFileTypes != null && !Array.isArray(opts.restrictions.allowedFileTypes)) {
        throw new TypeError('`restrictions.allowedFileTypes` must be an array');
      }

      return opts;
    };
  } // Because these operations are slow, we cannot run them for every file (if we are adding multiple files)


  validateAggregateRestrictions(existingFiles, addingFiles) {
    const {
      maxTotalFileSize,
      maxNumberOfFiles
    } = this.getOpts().restrictions;

    if (maxNumberOfFiles) {
      const nonGhostFiles = existingFiles.filter(f => !f.isGhost);

      if (nonGhostFiles.length + addingFiles.length > maxNumberOfFiles) {
        throw new RestrictionError(`${this.i18n('youCanOnlyUploadX', {
          smart_count: maxNumberOfFiles
        })}`);
      }
    }

    if (maxTotalFileSize) {
      let totalFilesSize = existingFiles.reduce((total, f) => total + f.size, 0);

      for (const addingFile of addingFiles) {
        if (addingFile.size != null) {
          // We can't check maxTotalFileSize if the size is unknown.
          totalFilesSize += addingFile.size;

          if (totalFilesSize > maxTotalFileSize) {
            throw new RestrictionError(this.i18n('exceedsSize', {
              size: _transloadit_prettier_bytes__WEBPACK_IMPORTED_MODULE_0__(maxTotalFileSize),
              file: addingFile.name
            }));
          }
        }
      }
    }
  }

  validateSingleFile(file) {
    const {
      maxFileSize,
      minFileSize,
      allowedFileTypes
    } = this.getOpts().restrictions;

    if (allowedFileTypes) {
      const isCorrectFileType = allowedFileTypes.some(type => {
        // check if this is a mime-type
        if (type.includes('/')) {
          if (!file.type) return false;
          return mime_match__WEBPACK_IMPORTED_MODULE_1__(file.type.replace(/;.*?$/, ''), type);
        } // otherwise this is likely an extension


        if (type[0] === '.' && file.extension) {
          return file.extension.toLowerCase() === type.slice(1).toLowerCase();
        }

        return false;
      });

      if (!isCorrectFileType) {
        const allowedFileTypesString = allowedFileTypes.join(', ');
        throw new RestrictionError(this.i18n('youCanOnlyUploadFileTypes', {
          types: allowedFileTypesString
        }), {
          file
        });
      }
    } // We can't check maxFileSize if the size is unknown.


    if (maxFileSize && file.size != null && file.size > maxFileSize) {
      throw new RestrictionError(this.i18n('exceedsSize', {
        size: _transloadit_prettier_bytes__WEBPACK_IMPORTED_MODULE_0__(maxFileSize),
        file: file.name
      }), {
        file
      });
    } // We can't check minFileSize if the size is unknown.


    if (minFileSize && file.size != null && file.size < minFileSize) {
      throw new RestrictionError(this.i18n('inferiorSize', {
        size: _transloadit_prettier_bytes__WEBPACK_IMPORTED_MODULE_0__(minFileSize)
      }), {
        file
      });
    }
  }

  validate(existingFiles, addingFiles) {
    addingFiles.forEach(addingFile => {
      this.validateSingleFile(addingFile);
    });
    this.validateAggregateRestrictions(existingFiles, addingFiles);
  }

  validateMinNumberOfFiles(files) {
    const {
      minNumberOfFiles
    } = this.getOpts().restrictions;

    if (Object.keys(files).length < minNumberOfFiles) {
      throw new RestrictionError(this.i18n('youHaveToAtLeastSelectX', {
        smart_count: minNumberOfFiles
      }));
    }
  }

  getMissingRequiredMetaFields(file) {
    const error = new RestrictionError(this.i18n('missingRequiredMetaFieldOnFile', {
      fileName: file.name
    }));
    const {
      requiredMetaFields
    } = this.getOpts().restrictions;
    const missingFields = [];

    for (const field of requiredMetaFields) {
      if (!Object.hasOwn(file.meta, field) || file.meta[field] === '') {
        missingFields.push(field);
      }
    }

    return {
      missingFields,
      error
    };
  }

}



/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/core/lib/UIPlugin.js":
/*!*****************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/core/lib/UIPlugin.js ***!
  \*****************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var _uppy_utils_lib_findDOMElement__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @uppy/utils/lib/findDOMElement */ "./src/jgDashboard/node_modules/@uppy/utils/lib/findDOMElement.js");
/* harmony import */ var _uppy_utils_lib_getTextDirection__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @uppy/utils/lib/getTextDirection */ "./src/jgDashboard/node_modules/@uppy/utils/lib/getTextDirection.js");
/* harmony import */ var _BasePlugin_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./BasePlugin.js */ "./src/jgDashboard/node_modules/@uppy/core/lib/BasePlugin.js");
function _classPrivateFieldLooseBase(receiver, privateKey) { if (!Object.prototype.hasOwnProperty.call(receiver, privateKey)) { throw new TypeError("attempted to use private field on non-instance"); } return receiver; }

var id = 0;

function _classPrivateFieldLooseKey(name) { return "__private_" + id++ + "_" + name; }





/**
 * Defer a frequent call to the microtask queue.
 *
 * @param {() => T} fn
 * @returns {Promise<T>}
 */

function debounce(fn) {
  let calling = null;
  let latestArgs = null;
  return function () {
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    latestArgs = args;

    if (!calling) {
      calling = Promise.resolve().then(() => {
        calling = null; // At this point `args` may be different from the most
        // recent state, if multiple calls happened since this task
        // was queued. So we use the `latestArgs`, which definitely
        // is the most recent call.

        return fn(...latestArgs);
      });
    }

    return calling;
  };
}
/**
 * UIPlugin is the extended version of BasePlugin to incorporate rendering with Preact.
 * Use this for plugins that need a user interface.
 *
 * For plugins without an user interface, see BasePlugin.
 */


var _updateUI = /*#__PURE__*/_classPrivateFieldLooseKey("updateUI");

class UIPlugin extends _BasePlugin_js__WEBPACK_IMPORTED_MODULE_3__["default"] {
  constructor() {
    super(...arguments);
    Object.defineProperty(this, _updateUI, {
      writable: true,
      value: void 0
    });
  }

  getTargetPlugin(target) {
    let targetPlugin;

    if (typeof target === 'object' && target instanceof UIPlugin) {
      // Targeting a plugin *instance*
      targetPlugin = target;
    } else if (typeof target === 'function') {
      // Targeting a plugin type
      const Target = target; // Find the target plugin instance.

      this.uppy.iteratePlugins(p => {
        if (p instanceof Target) {
          targetPlugin = p;
        }
      });
    }

    return targetPlugin;
  }
  /**
   * Check if supplied `target` is a DOM element or an `object`.
   * If it’s an object — target is a plugin, and we search `plugins`
   * for a plugin with same name and return its target.
   */


  mount(target, plugin) {
    const callerPluginName = plugin.id;
    const targetElement = (0,_uppy_utils_lib_findDOMElement__WEBPACK_IMPORTED_MODULE_1__["default"])(target);

    if (targetElement) {
      this.isTargetDOMEl = true; // When target is <body> with a single <div> element,
      // Preact thinks it’s the Uppy root element in there when doing a diff,
      // and destroys it. So we are creating a fragment (could be empty div)

      const uppyRootElement = document.createElement('div');
      uppyRootElement.classList.add('uppy-Root'); // API for plugins that require a synchronous rerender.

      _classPrivateFieldLooseBase(this, _updateUI)[_updateUI] = debounce(state => {
        // plugin could be removed, but this.rerender is debounced below,
        // so it could still be called even after uppy.removePlugin or uppy.close
        // hence the check
        if (!this.uppy.getPlugin(this.id)) return;
        (0,preact__WEBPACK_IMPORTED_MODULE_0__.render)(this.render(state), uppyRootElement);
        this.afterUpdate();
      });
      this.uppy.log(`Installing ${callerPluginName} to a DOM element '${target}'`);

      if (this.opts.replaceTargetContent) {
        // Doing render(h(null), targetElement), which should have been
        // a better way, since because the component might need to do additional cleanup when it is removed,
        // stopped working — Preact just adds null into target, not replacing
        targetElement.innerHTML = '';
      }

      (0,preact__WEBPACK_IMPORTED_MODULE_0__.render)(this.render(this.uppy.getState()), uppyRootElement);
      this.el = uppyRootElement;
      targetElement.appendChild(uppyRootElement); // Set the text direction if the page has not defined one.

      uppyRootElement.dir = this.opts.direction || (0,_uppy_utils_lib_getTextDirection__WEBPACK_IMPORTED_MODULE_2__["default"])(uppyRootElement) || 'ltr';
      this.onMount();
      return this.el;
    }

    const targetPlugin = this.getTargetPlugin(target);

    if (targetPlugin) {
      this.uppy.log(`Installing ${callerPluginName} to ${targetPlugin.id}`);
      this.parent = targetPlugin;
      this.el = targetPlugin.addTarget(plugin);
      this.onMount();
      return this.el;
    }

    this.uppy.log(`Not installing ${callerPluginName}`);
    let message = `Invalid target option given to ${callerPluginName}.`;

    if (typeof target === 'function') {
      message += ' The given target is not a Plugin class. ' + 'Please check that you\'re not specifying a React Component instead of a plugin. ' + 'If you are using @uppy/* packages directly, make sure you have only 1 version of @uppy/core installed: ' + 'run `npm ls @uppy/core` on the command line and verify that all the versions match and are deduped correctly.';
    } else {
      message += 'If you meant to target an HTML element, please make sure that the element exists. ' + 'Check that the <script> tag initializing Uppy is right before the closing </body> tag at the end of the page. ' + '(see https://github.com/transloadit/uppy/issues/1042)\n\n' + 'If you meant to target a plugin, please confirm that your `import` statements or `require` calls are correct.';
    }

    throw new Error(message);
  }

  update(state) {
    if (this.el != null) {
      var _classPrivateFieldLoo, _classPrivateFieldLoo2;

      (_classPrivateFieldLoo = (_classPrivateFieldLoo2 = _classPrivateFieldLooseBase(this, _updateUI))[_updateUI]) == null ? void 0 : _classPrivateFieldLoo.call(_classPrivateFieldLoo2, state);
    }
  }

  unmount() {
    if (this.isTargetDOMEl) {
      var _this$el;

      (_this$el = this.el) == null ? void 0 : _this$el.remove();
    }

    this.onUnmount();
  } // eslint-disable-next-line class-methods-use-this


  onMount() {} // eslint-disable-next-line class-methods-use-this


  onUnmount() {}

}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (UIPlugin);

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/core/lib/Uppy.js":
/*!*************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/core/lib/Uppy.js ***!
  \*************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _uppy_utils_lib_Translator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @uppy/utils/lib/Translator */ "./src/jgDashboard/node_modules/@uppy/utils/lib/Translator.js");
/* harmony import */ var namespace_emitter__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! namespace-emitter */ "./src/jgDashboard/node_modules/namespace-emitter/index.js");
/* harmony import */ var nanoid_non_secure__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! nanoid/non-secure */ "./src/jgDashboard/node_modules/nanoid/non-secure/index.js");
/* harmony import */ var lodash_throttle__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! lodash.throttle */ "./src/jgDashboard/node_modules/lodash.throttle/index.js");
/* harmony import */ var _uppy_store_default__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @uppy/store-default */ "./src/jgDashboard/node_modules/@uppy/store-default/lib/index.js");
/* harmony import */ var _uppy_utils_lib_getFileType__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @uppy/utils/lib/getFileType */ "./src/jgDashboard/node_modules/@uppy/utils/lib/getFileType.js");
/* harmony import */ var _uppy_utils_lib_getFileNameAndExtension__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @uppy/utils/lib/getFileNameAndExtension */ "./src/jgDashboard/node_modules/@uppy/utils/lib/getFileNameAndExtension.js");
/* harmony import */ var _uppy_utils_lib_generateFileID__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @uppy/utils/lib/generateFileID */ "./src/jgDashboard/node_modules/@uppy/utils/lib/generateFileID.js");
/* harmony import */ var _supportsUploadProgress_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./supportsUploadProgress.js */ "./src/jgDashboard/node_modules/@uppy/core/lib/supportsUploadProgress.js");
/* harmony import */ var _getFileName_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./getFileName.js */ "./src/jgDashboard/node_modules/@uppy/core/lib/getFileName.js");
/* harmony import */ var _loggers_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./loggers.js */ "./src/jgDashboard/node_modules/@uppy/core/lib/loggers.js");
/* harmony import */ var _Restricter_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./Restricter.js */ "./src/jgDashboard/node_modules/@uppy/core/lib/Restricter.js");
/* harmony import */ var _locale_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./locale.js */ "./src/jgDashboard/node_modules/@uppy/core/lib/locale.js");
let _Symbol$for, _Symbol$for2;

function _classPrivateFieldLooseBase(receiver, privateKey) { if (!Object.prototype.hasOwnProperty.call(receiver, privateKey)) { throw new TypeError("attempted to use private field on non-instance"); } return receiver; }

var id = 0;

function _classPrivateFieldLooseKey(name) { return "__private_" + id++ + "_" + name; }

/* eslint-disable max-classes-per-file */

/* global AggregateError */












const packageJson = {
  "version": "3.2.0"
};

/**
 * Uppy Core module.
 * Manages plugins, state updates, acts as an event bus,
 * adds/removes files and metadata.
 */

var _plugins = /*#__PURE__*/_classPrivateFieldLooseKey("plugins");

var _restricter = /*#__PURE__*/_classPrivateFieldLooseKey("restricter");

var _storeUnsubscribe = /*#__PURE__*/_classPrivateFieldLooseKey("storeUnsubscribe");

var _emitter = /*#__PURE__*/_classPrivateFieldLooseKey("emitter");

var _preProcessors = /*#__PURE__*/_classPrivateFieldLooseKey("preProcessors");

var _uploaders = /*#__PURE__*/_classPrivateFieldLooseKey("uploaders");

var _postProcessors = /*#__PURE__*/_classPrivateFieldLooseKey("postProcessors");

var _informAndEmit = /*#__PURE__*/_classPrivateFieldLooseKey("informAndEmit");

var _checkRequiredMetaFieldsOnFile = /*#__PURE__*/_classPrivateFieldLooseKey("checkRequiredMetaFieldsOnFile");

var _checkRequiredMetaFields = /*#__PURE__*/_classPrivateFieldLooseKey("checkRequiredMetaFields");

var _assertNewUploadAllowed = /*#__PURE__*/_classPrivateFieldLooseKey("assertNewUploadAllowed");

var _transformFile = /*#__PURE__*/_classPrivateFieldLooseKey("transformFile");

var _startIfAutoProceed = /*#__PURE__*/_classPrivateFieldLooseKey("startIfAutoProceed");

var _checkAndUpdateFileState = /*#__PURE__*/_classPrivateFieldLooseKey("checkAndUpdateFileState");

var _addListeners = /*#__PURE__*/_classPrivateFieldLooseKey("addListeners");

var _updateOnlineStatus = /*#__PURE__*/_classPrivateFieldLooseKey("updateOnlineStatus");

var _createUpload = /*#__PURE__*/_classPrivateFieldLooseKey("createUpload");

var _getUpload = /*#__PURE__*/_classPrivateFieldLooseKey("getUpload");

var _removeUpload = /*#__PURE__*/_classPrivateFieldLooseKey("removeUpload");

var _runUpload = /*#__PURE__*/_classPrivateFieldLooseKey("runUpload");

_Symbol$for = Symbol.for('uppy test: getPlugins');
_Symbol$for2 = Symbol.for('uppy test: createUpload');

class Uppy {
  /** @type {Record<string, BasePlugin[]>} */

  /**
   * Instantiate Uppy
   *
   * @param {object} opts — Uppy options
   */
  constructor(_opts) {
    Object.defineProperty(this, _runUpload, {
      value: _runUpload2
    });
    Object.defineProperty(this, _removeUpload, {
      value: _removeUpload2
    });
    Object.defineProperty(this, _getUpload, {
      value: _getUpload2
    });
    Object.defineProperty(this, _createUpload, {
      value: _createUpload2
    });
    Object.defineProperty(this, _addListeners, {
      value: _addListeners2
    });
    Object.defineProperty(this, _checkAndUpdateFileState, {
      value: _checkAndUpdateFileState2
    });
    Object.defineProperty(this, _startIfAutoProceed, {
      value: _startIfAutoProceed2
    });
    Object.defineProperty(this, _transformFile, {
      value: _transformFile2
    });
    Object.defineProperty(this, _assertNewUploadAllowed, {
      value: _assertNewUploadAllowed2
    });
    Object.defineProperty(this, _checkRequiredMetaFields, {
      value: _checkRequiredMetaFields2
    });
    Object.defineProperty(this, _checkRequiredMetaFieldsOnFile, {
      value: _checkRequiredMetaFieldsOnFile2
    });
    Object.defineProperty(this, _informAndEmit, {
      value: _informAndEmit2
    });
    Object.defineProperty(this, _plugins, {
      writable: true,
      value: Object.create(null)
    });
    Object.defineProperty(this, _restricter, {
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, _storeUnsubscribe, {
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, _emitter, {
      writable: true,
      value: namespace_emitter__WEBPACK_IMPORTED_MODULE_1__()
    });
    Object.defineProperty(this, _preProcessors, {
      writable: true,
      value: new Set()
    });
    Object.defineProperty(this, _uploaders, {
      writable: true,
      value: new Set()
    });
    Object.defineProperty(this, _postProcessors, {
      writable: true,
      value: new Set()
    });
    Object.defineProperty(this, _updateOnlineStatus, {
      writable: true,
      value: this.updateOnlineStatus.bind(this)
    });
    this.defaultLocale = _locale_js__WEBPACK_IMPORTED_MODULE_11__["default"];
    const defaultOptions = {
      id: 'uppy',
      autoProceed: false,
      allowMultipleUploadBatches: true,
      debug: false,
      restrictions: _Restricter_js__WEBPACK_IMPORTED_MODULE_10__.defaultOptions,
      meta: {},
      onBeforeFileAdded: currentFile => currentFile,
      onBeforeUpload: files => files,
      store: new _uppy_store_default__WEBPACK_IMPORTED_MODULE_3__["default"](),
      logger: _loggers_js__WEBPACK_IMPORTED_MODULE_9__.justErrorsLogger,
      infoTimeout: 5000
    }; // Merge default options with the ones set by user,
    // making sure to merge restrictions too

    this.opts = { ...defaultOptions,
      ..._opts,
      restrictions: { ...defaultOptions.restrictions,
        ...(_opts && _opts.restrictions)
      }
    }; // Support debug: true for backwards-compatability, unless logger is set in opts
    // opts instead of this.opts to avoid comparing objects — we set logger: justErrorsLogger in defaultOptions

    if (_opts && _opts.logger && _opts.debug) {
      this.log('You are using a custom `logger`, but also set `debug: true`, which uses built-in logger to output logs to console. Ignoring `debug: true` and using your custom `logger`.', 'warning');
    } else if (_opts && _opts.debug) {
      this.opts.logger = _loggers_js__WEBPACK_IMPORTED_MODULE_9__.debugLogger;
    }

    this.log(`Using Core v${this.constructor.VERSION}`);
    this.i18nInit(); // ___Why throttle at 500ms?
    //    - We must throttle at >250ms for superfocus in Dashboard to work well
    //    (because animation takes 0.25s, and we want to wait for all animations to be over before refocusing).
    //    [Practical Check]: if thottle is at 100ms, then if you are uploading a file,
    //    and click 'ADD MORE FILES', - focus won't activate in Firefox.
    //    - We must throttle at around >500ms to avoid performance lags.
    //    [Practical Check] Firefox, try to upload a big file for a prolonged period of time. Laptop will start to heat up.

    this.calculateProgress = lodash_throttle__WEBPACK_IMPORTED_MODULE_2__(this.calculateProgress.bind(this), 500, {
      leading: true,
      trailing: true
    });
    this.store = this.opts.store;
    this.setState({
      plugins: {},
      files: {},
      currentUploads: {},
      allowNewUpload: true,
      capabilities: {
        uploadProgress: (0,_supportsUploadProgress_js__WEBPACK_IMPORTED_MODULE_7__["default"])(),
        individualCancellation: true,
        resumableUploads: false
      },
      totalProgress: 0,
      meta: { ...this.opts.meta
      },
      info: [],
      recoveredState: null
    });
    _classPrivateFieldLooseBase(this, _restricter)[_restricter] = new _Restricter_js__WEBPACK_IMPORTED_MODULE_10__.Restricter(() => this.opts, this.i18n);
    _classPrivateFieldLooseBase(this, _storeUnsubscribe)[_storeUnsubscribe] = this.store.subscribe((prevState, nextState, patch) => {
      this.emit('state-update', prevState, nextState, patch);
      this.updateAll(nextState);
    }); // Exposing uppy object on window for debugging and testing

    if (this.opts.debug && typeof window !== 'undefined') {
      window[this.opts.id] = this;
    }

    _classPrivateFieldLooseBase(this, _addListeners)[_addListeners]();
  }

  emit(event) {
    for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
      args[_key - 1] = arguments[_key];
    }

    _classPrivateFieldLooseBase(this, _emitter)[_emitter].emit(event, ...args);
  }

  on(event, callback) {
    _classPrivateFieldLooseBase(this, _emitter)[_emitter].on(event, callback);

    return this;
  }

  once(event, callback) {
    _classPrivateFieldLooseBase(this, _emitter)[_emitter].once(event, callback);

    return this;
  }

  off(event, callback) {
    _classPrivateFieldLooseBase(this, _emitter)[_emitter].off(event, callback);

    return this;
  }
  /**
   * Iterate on all plugins and run `update` on them.
   * Called each time state changes.
   *
   */


  updateAll(state) {
    this.iteratePlugins(plugin => {
      plugin.update(state);
    });
  }
  /**
   * Updates state with a patch
   *
   * @param {object} patch {foo: 'bar'}
   */


  setState(patch) {
    this.store.setState(patch);
  }
  /**
   * Returns current state.
   *
   * @returns {object}
   */


  getState() {
    return this.store.getState();
  }

  patchFilesState(filesWithNewState) {
    const existingFilesState = this.getState().files;
    this.setState({
      files: { ...existingFilesState,
        ...Object.fromEntries(Object.entries(filesWithNewState).map(_ref => {
          let [fileID, newFileState] = _ref;
          return [fileID, { ...existingFilesState[fileID],
            ...newFileState
          }];
        }))
      }
    });
  }
  /**
   * Shorthand to set state for a specific file.
   */


  setFileState(fileID, state) {
    if (!this.getState().files[fileID]) {
      throw new Error(`Can’t set state for ${fileID} (the file could have been removed)`);
    }

    this.patchFilesState({
      [fileID]: state
    });
  }

  i18nInit() {
    const translator = new _uppy_utils_lib_Translator__WEBPACK_IMPORTED_MODULE_0__["default"]([this.defaultLocale, this.opts.locale]);
    this.i18n = translator.translate.bind(translator);
    this.i18nArray = translator.translateArray.bind(translator);
    this.locale = translator.locale;
  }

  setOptions(newOpts) {
    this.opts = { ...this.opts,
      ...newOpts,
      restrictions: { ...this.opts.restrictions,
        ...(newOpts && newOpts.restrictions)
      }
    };

    if (newOpts.meta) {
      this.setMeta(newOpts.meta);
    }

    this.i18nInit();

    if (newOpts.locale) {
      this.iteratePlugins(plugin => {
        plugin.setOptions(newOpts);
      });
    } // Note: this is not the preact `setState`, it's an internal function that has the same name.


    this.setState(); // so that UI re-renders with new options
  }

  resetProgress() {
    const defaultProgress = {
      percentage: 0,
      bytesUploaded: 0,
      uploadComplete: false,
      uploadStarted: null
    };
    const files = { ...this.getState().files
    };
    const updatedFiles = {};
    Object.keys(files).forEach(fileID => {
      updatedFiles[fileID] = { ...files[fileID],
        progress: { ...files[fileID].progress,
          ...defaultProgress
        }
      };
    });
    this.setState({
      files: updatedFiles,
      totalProgress: 0,
      allowNewUpload: true,
      error: null,
      recoveredState: null
    });
    this.emit('reset-progress');
  }

  addPreProcessor(fn) {
    _classPrivateFieldLooseBase(this, _preProcessors)[_preProcessors].add(fn);
  }

  removePreProcessor(fn) {
    return _classPrivateFieldLooseBase(this, _preProcessors)[_preProcessors].delete(fn);
  }

  addPostProcessor(fn) {
    _classPrivateFieldLooseBase(this, _postProcessors)[_postProcessors].add(fn);
  }

  removePostProcessor(fn) {
    return _classPrivateFieldLooseBase(this, _postProcessors)[_postProcessors].delete(fn);
  }

  addUploader(fn) {
    _classPrivateFieldLooseBase(this, _uploaders)[_uploaders].add(fn);
  }

  removeUploader(fn) {
    return _classPrivateFieldLooseBase(this, _uploaders)[_uploaders].delete(fn);
  }

  setMeta(data) {
    const updatedMeta = { ...this.getState().meta,
      ...data
    };
    const updatedFiles = { ...this.getState().files
    };
    Object.keys(updatedFiles).forEach(fileID => {
      updatedFiles[fileID] = { ...updatedFiles[fileID],
        meta: { ...updatedFiles[fileID].meta,
          ...data
        }
      };
    });
    this.log('Adding metadata:');
    this.log(data);
    this.setState({
      meta: updatedMeta,
      files: updatedFiles
    });
  }

  setFileMeta(fileID, data) {
    const updatedFiles = { ...this.getState().files
    };

    if (!updatedFiles[fileID]) {
      this.log('Was trying to set metadata for a file that has been removed: ', fileID);
      return;
    }

    const newMeta = { ...updatedFiles[fileID].meta,
      ...data
    };
    updatedFiles[fileID] = { ...updatedFiles[fileID],
      meta: newMeta
    };
    this.setState({
      files: updatedFiles
    });
  }
  /**
   * Get a file object.
   *
   * @param {string} fileID The ID of the file object to return.
   */


  getFile(fileID) {
    return this.getState().files[fileID];
  }
  /**
   * Get all files in an array.
   */


  getFiles() {
    const {
      files
    } = this.getState();
    return Object.values(files);
  }

  getFilesByIds(ids) {
    return ids.map(id => this.getFile(id));
  }

  getObjectOfFilesPerState() {
    const {
      files: filesObject,
      totalProgress,
      error
    } = this.getState();
    const files = Object.values(filesObject);
    const inProgressFiles = files.filter(_ref2 => {
      let {
        progress
      } = _ref2;
      return !progress.uploadComplete && progress.uploadStarted;
    });
    const newFiles = files.filter(file => !file.progress.uploadStarted);
    const startedFiles = files.filter(file => file.progress.uploadStarted || file.progress.preprocess || file.progress.postprocess);
    const uploadStartedFiles = files.filter(file => file.progress.uploadStarted);
    const pausedFiles = files.filter(file => file.isPaused);
    const completeFiles = files.filter(file => file.progress.uploadComplete);
    const erroredFiles = files.filter(file => file.error);
    const inProgressNotPausedFiles = inProgressFiles.filter(file => !file.isPaused);
    const processingFiles = files.filter(file => file.progress.preprocess || file.progress.postprocess);
    return {
      newFiles,
      startedFiles,
      uploadStartedFiles,
      pausedFiles,
      completeFiles,
      erroredFiles,
      inProgressFiles,
      inProgressNotPausedFiles,
      processingFiles,
      isUploadStarted: uploadStartedFiles.length > 0,
      isAllComplete: totalProgress === 100 && completeFiles.length === files.length && processingFiles.length === 0,
      isAllErrored: !!error && erroredFiles.length === files.length,
      isAllPaused: inProgressFiles.length !== 0 && pausedFiles.length === inProgressFiles.length,
      isUploadInProgress: inProgressFiles.length > 0,
      isSomeGhost: files.some(file => file.isGhost)
    };
  }
  /*
  * @constructs
  * @param { Error[] } errors
  * @param { undefined } file
  */

  /*
  * @constructs
  * @param { RestrictionError } error
  */


  validateRestrictions(file, files) {
    if (files === void 0) {
      files = this.getFiles();
    }

    try {
      _classPrivateFieldLooseBase(this, _restricter)[_restricter].validate(files, [file]);
    } catch (err) {
      return err;
    }

    return null;
  }

  checkIfFileAlreadyExists(fileID) {
    const {
      files
    } = this.getState();

    if (files[fileID] && !files[fileID].isGhost) {
      return true;
    }

    return false;
  }
  /**
   * Create a file state object based on user-provided `addFile()` options.
   */


  /**
   * Add a new file to `state.files`. This will run `onBeforeFileAdded`,
   * try to guess file type in a clever way, check file against restrictions,
   * and start an upload if `autoProceed === true`.
   *
   * @param {object} file object to add
   * @returns {string} id for the added file
   */
  addFile(file) {
    _classPrivateFieldLooseBase(this, _assertNewUploadAllowed)[_assertNewUploadAllowed](file);

    const {
      nextFilesState,
      validFilesToAdd,
      errors
    } = _classPrivateFieldLooseBase(this, _checkAndUpdateFileState)[_checkAndUpdateFileState]([file]);

    const restrictionErrors = errors.filter(error => error.isRestriction);

    _classPrivateFieldLooseBase(this, _informAndEmit)[_informAndEmit](restrictionErrors);

    if (errors.length > 0) throw errors[0];
    this.setState({
      files: nextFilesState
    });
    const [firstValidFileToAdd] = validFilesToAdd;
    this.emit('file-added', firstValidFileToAdd);
    this.emit('files-added', validFilesToAdd);
    this.log(`Added file: ${firstValidFileToAdd.name}, ${firstValidFileToAdd.id}, mime type: ${firstValidFileToAdd.type}`);

    _classPrivateFieldLooseBase(this, _startIfAutoProceed)[_startIfAutoProceed]();

    return firstValidFileToAdd.id;
  }
  /**
   * Add multiple files to `state.files`. See the `addFile()` documentation.
   *
   * If an error occurs while adding a file, it is logged and the user is notified.
   * This is good for UI plugins, but not for programmatic use.
   * Programmatic users should usually still use `addFile()` on individual files.
   */


  addFiles(fileDescriptors) {
    _classPrivateFieldLooseBase(this, _assertNewUploadAllowed)[_assertNewUploadAllowed]();

    const {
      nextFilesState,
      validFilesToAdd,
      errors
    } = _classPrivateFieldLooseBase(this, _checkAndUpdateFileState)[_checkAndUpdateFileState](fileDescriptors);

    const restrictionErrors = errors.filter(error => error.isRestriction);

    _classPrivateFieldLooseBase(this, _informAndEmit)[_informAndEmit](restrictionErrors);

    const nonRestrictionErrors = errors.filter(error => !error.isRestriction);

    if (nonRestrictionErrors.length > 0) {
      let message = 'Multiple errors occurred while adding files:\n';
      nonRestrictionErrors.forEach(subError => {
        message += `\n * ${subError.message}`;
      });
      this.info({
        message: this.i18n('addBulkFilesFailed', {
          smart_count: nonRestrictionErrors.length
        }),
        details: message
      }, 'error', this.opts.infoTimeout);

      if (typeof AggregateError === 'function') {
        throw new AggregateError(nonRestrictionErrors, message);
      } else {
        const err = new Error(message);
        err.errors = nonRestrictionErrors;
        throw err;
      }
    } // OK, we haven't thrown an error, we can start updating state and emitting events now:


    this.setState({
      files: nextFilesState
    });
    validFilesToAdd.forEach(file => {
      this.emit('file-added', file);
    });
    this.emit('files-added', validFilesToAdd);

    if (validFilesToAdd.length > 5) {
      this.log(`Added batch of ${validFilesToAdd.length} files`);
    } else {
      Object.values(validFilesToAdd).forEach(file => {
        this.log(`Added file: ${file.name}\n id: ${file.id}\n type: ${file.type}`);
      });
    }

    if (validFilesToAdd.length > 0) {
      _classPrivateFieldLooseBase(this, _startIfAutoProceed)[_startIfAutoProceed]();
    }
  }

  removeFiles(fileIDs, reason) {
    const {
      files,
      currentUploads
    } = this.getState();
    const updatedFiles = { ...files
    };
    const updatedUploads = { ...currentUploads
    };
    const removedFiles = Object.create(null);
    fileIDs.forEach(fileID => {
      if (files[fileID]) {
        removedFiles[fileID] = files[fileID];
        delete updatedFiles[fileID];
      }
    }); // Remove files from the `fileIDs` list in each upload.

    function fileIsNotRemoved(uploadFileID) {
      return removedFiles[uploadFileID] === undefined;
    }

    Object.keys(updatedUploads).forEach(uploadID => {
      const newFileIDs = currentUploads[uploadID].fileIDs.filter(fileIsNotRemoved); // Remove the upload if no files are associated with it anymore.

      if (newFileIDs.length === 0) {
        delete updatedUploads[uploadID];
        return;
      }

      const {
        capabilities
      } = this.getState();

      if (newFileIDs.length !== currentUploads[uploadID].fileIDs.length && !capabilities.individualCancellation) {
        throw new Error('individualCancellation is disabled');
      }

      updatedUploads[uploadID] = { ...currentUploads[uploadID],
        fileIDs: newFileIDs
      };
    });
    const stateUpdate = {
      currentUploads: updatedUploads,
      files: updatedFiles
    }; // If all files were removed - allow new uploads,
    // and clear recoveredState

    if (Object.keys(updatedFiles).length === 0) {
      stateUpdate.allowNewUpload = true;
      stateUpdate.error = null;
      stateUpdate.recoveredState = null;
    }

    this.setState(stateUpdate);
    this.calculateTotalProgress();
    const removedFileIDs = Object.keys(removedFiles);
    removedFileIDs.forEach(fileID => {
      this.emit('file-removed', removedFiles[fileID], reason);
    });

    if (removedFileIDs.length > 5) {
      this.log(`Removed ${removedFileIDs.length} files`);
    } else {
      this.log(`Removed files: ${removedFileIDs.join(', ')}`);
    }
  }

  removeFile(fileID, reason) {
    if (reason === void 0) {
      reason = null;
    }

    this.removeFiles([fileID], reason);
  }

  pauseResume(fileID) {
    if (!this.getState().capabilities.resumableUploads || this.getFile(fileID).uploadComplete) {
      return undefined;
    }

    const wasPaused = this.getFile(fileID).isPaused || false;
    const isPaused = !wasPaused;
    this.setFileState(fileID, {
      isPaused
    });
    this.emit('upload-pause', fileID, isPaused);
    return isPaused;
  }

  pauseAll() {
    const updatedFiles = { ...this.getState().files
    };
    const inProgressUpdatedFiles = Object.keys(updatedFiles).filter(file => {
      return !updatedFiles[file].progress.uploadComplete && updatedFiles[file].progress.uploadStarted;
    });
    inProgressUpdatedFiles.forEach(file => {
      const updatedFile = { ...updatedFiles[file],
        isPaused: true
      };
      updatedFiles[file] = updatedFile;
    });
    this.setState({
      files: updatedFiles
    });
    this.emit('pause-all');
  }

  resumeAll() {
    const updatedFiles = { ...this.getState().files
    };
    const inProgressUpdatedFiles = Object.keys(updatedFiles).filter(file => {
      return !updatedFiles[file].progress.uploadComplete && updatedFiles[file].progress.uploadStarted;
    });
    inProgressUpdatedFiles.forEach(file => {
      const updatedFile = { ...updatedFiles[file],
        isPaused: false,
        error: null
      };
      updatedFiles[file] = updatedFile;
    });
    this.setState({
      files: updatedFiles
    });
    this.emit('resume-all');
  }

  retryAll() {
    const updatedFiles = { ...this.getState().files
    };
    const filesToRetry = Object.keys(updatedFiles).filter(file => {
      return updatedFiles[file].error;
    });
    filesToRetry.forEach(file => {
      const updatedFile = { ...updatedFiles[file],
        isPaused: false,
        error: null
      };
      updatedFiles[file] = updatedFile;
    });
    this.setState({
      files: updatedFiles,
      error: null
    });
    this.emit('retry-all', filesToRetry);

    if (filesToRetry.length === 0) {
      return Promise.resolve({
        successful: [],
        failed: []
      });
    }

    const uploadID = _classPrivateFieldLooseBase(this, _createUpload)[_createUpload](filesToRetry, {
      forceAllowNewUpload: true // create new upload even if allowNewUpload: false

    });

    return _classPrivateFieldLooseBase(this, _runUpload)[_runUpload](uploadID);
  }

  cancelAll(_temp) {
    let {
      reason = 'user'
    } = _temp === void 0 ? {} : _temp;
    this.emit('cancel-all', {
      reason
    }); // Only remove existing uploads if user is canceling

    if (reason === 'user') {
      const {
        files
      } = this.getState();
      const fileIDs = Object.keys(files);

      if (fileIDs.length) {
        this.removeFiles(fileIDs, 'cancel-all');
      }

      this.setState({
        totalProgress: 0,
        error: null,
        recoveredState: null
      });
    }
  }

  retryUpload(fileID) {
    this.setFileState(fileID, {
      error: null,
      isPaused: false
    });
    this.emit('upload-retry', fileID);

    const uploadID = _classPrivateFieldLooseBase(this, _createUpload)[_createUpload]([fileID], {
      forceAllowNewUpload: true // create new upload even if allowNewUpload: false

    });

    return _classPrivateFieldLooseBase(this, _runUpload)[_runUpload](uploadID);
  }

  logout() {
    this.iteratePlugins(plugin => {
      if (plugin.provider && plugin.provider.logout) {
        plugin.provider.logout();
      }
    });
  }

  calculateProgress(file, data) {
    if (file == null || !this.getFile(file.id)) {
      this.log(`Not setting progress for a file that has been removed: ${file == null ? void 0 : file.id}`);
      return;
    } // bytesTotal may be null or zero; in that case we can't divide by it


    const canHavePercentage = Number.isFinite(data.bytesTotal) && data.bytesTotal > 0;
    this.setFileState(file.id, {
      progress: { ...this.getFile(file.id).progress,
        bytesUploaded: data.bytesUploaded,
        bytesTotal: data.bytesTotal,
        percentage: canHavePercentage ? Math.round(data.bytesUploaded / data.bytesTotal * 100) : 0
      }
    });
    this.calculateTotalProgress();
  }

  calculateTotalProgress() {
    // calculate total progress, using the number of files currently uploading,
    // multiplied by 100 and the summ of individual progress of each file
    const files = this.getFiles();
    const inProgress = files.filter(file => {
      return file.progress.uploadStarted || file.progress.preprocess || file.progress.postprocess;
    });

    if (inProgress.length === 0) {
      this.emit('progress', 0);
      this.setState({
        totalProgress: 0
      });
      return;
    }

    const sizedFiles = inProgress.filter(file => file.progress.bytesTotal != null);
    const unsizedFiles = inProgress.filter(file => file.progress.bytesTotal == null);

    if (sizedFiles.length === 0) {
      const progressMax = inProgress.length * 100;
      const currentProgress = unsizedFiles.reduce((acc, file) => {
        return acc + file.progress.percentage;
      }, 0);
      const totalProgress = Math.round(currentProgress / progressMax * 100);
      this.setState({
        totalProgress
      });
      return;
    }

    let totalSize = sizedFiles.reduce((acc, file) => {
      return acc + file.progress.bytesTotal;
    }, 0);
    const averageSize = totalSize / sizedFiles.length;
    totalSize += averageSize * unsizedFiles.length;
    let uploadedSize = 0;
    sizedFiles.forEach(file => {
      uploadedSize += file.progress.bytesUploaded;
    });
    unsizedFiles.forEach(file => {
      uploadedSize += averageSize * (file.progress.percentage || 0) / 100;
    });
    let totalProgress = totalSize === 0 ? 0 : Math.round(uploadedSize / totalSize * 100); // hot fix, because:
    // uploadedSize ended up larger than totalSize, resulting in 1325% total

    if (totalProgress > 100) {
      totalProgress = 100;
    }

    this.setState({
      totalProgress
    });
    this.emit('progress', totalProgress);
  }
  /**
   * Registers listeners for all global actions, like:
   * `error`, `file-removed`, `upload-progress`
   */


  updateOnlineStatus() {
    const online = typeof window.navigator.onLine !== 'undefined' ? window.navigator.onLine : true;

    if (!online) {
      this.emit('is-offline');
      this.info(this.i18n('noInternetConnection'), 'error', 0);
      this.wasOffline = true;
    } else {
      this.emit('is-online');

      if (this.wasOffline) {
        this.emit('back-online');
        this.info(this.i18n('connectedToInternet'), 'success', 3000);
        this.wasOffline = false;
      }
    }
  }

  getID() {
    return this.opts.id;
  }
  /**
   * Registers a plugin with Core.
   *
   * @param {object} Plugin object
   * @param {object} [opts] object with options to be passed to Plugin
   * @returns {object} self for chaining
   */
  // eslint-disable-next-line no-shadow


  use(Plugin, opts) {
    if (typeof Plugin !== 'function') {
      const msg = `Expected a plugin class, but got ${Plugin === null ? 'null' : typeof Plugin}.` + ' Please verify that the plugin was imported and spelled correctly.';
      throw new TypeError(msg);
    } // Instantiate


    const plugin = new Plugin(this, opts);
    const pluginId = plugin.id;

    if (!pluginId) {
      throw new Error('Your plugin must have an id');
    }

    if (!plugin.type) {
      throw new Error('Your plugin must have a type');
    }

    const existsPluginAlready = this.getPlugin(pluginId);

    if (existsPluginAlready) {
      const msg = `Already found a plugin named '${existsPluginAlready.id}'. ` + `Tried to use: '${pluginId}'.\n` + 'Uppy plugins must have unique `id` options. See https://uppy.io/docs/plugins/#id.';
      throw new Error(msg);
    }

    if (Plugin.VERSION) {
      this.log(`Using ${pluginId} v${Plugin.VERSION}`);
    }

    if (plugin.type in _classPrivateFieldLooseBase(this, _plugins)[_plugins]) {
      _classPrivateFieldLooseBase(this, _plugins)[_plugins][plugin.type].push(plugin);
    } else {
      _classPrivateFieldLooseBase(this, _plugins)[_plugins][plugin.type] = [plugin];
    }

    plugin.install();
    return this;
  }
  /**
   * Find one Plugin by name.
   *
   * @param {string} id plugin id
   * @returns {BasePlugin|undefined}
   */


  getPlugin(id) {
    for (const plugins of Object.values(_classPrivateFieldLooseBase(this, _plugins)[_plugins])) {
      const foundPlugin = plugins.find(plugin => plugin.id === id);
      if (foundPlugin != null) return foundPlugin;
    }

    return undefined;
  }

  [_Symbol$for](type) {
    return _classPrivateFieldLooseBase(this, _plugins)[_plugins][type];
  }
  /**
   * Iterate through all `use`d plugins.
   *
   * @param {Function} method that will be run on each plugin
   */


  iteratePlugins(method) {
    Object.values(_classPrivateFieldLooseBase(this, _plugins)[_plugins]).flat(1).forEach(method);
  }
  /**
   * Uninstall and remove a plugin.
   *
   * @param {object} instance The plugin instance to remove.
   */


  removePlugin(instance) {
    this.log(`Removing plugin ${instance.id}`);
    this.emit('plugin-remove', instance);

    if (instance.uninstall) {
      instance.uninstall();
    }

    const list = _classPrivateFieldLooseBase(this, _plugins)[_plugins][instance.type]; // list.indexOf failed here, because Vue3 converted the plugin instance
    // to a Proxy object, which failed the strict comparison test:
    // obj !== objProxy


    const index = list.findIndex(item => item.id === instance.id);

    if (index !== -1) {
      list.splice(index, 1);
    }

    const state = this.getState();
    const updatedState = {
      plugins: { ...state.plugins,
        [instance.id]: undefined
      }
    };
    this.setState(updatedState);
  }
  /**
   * Uninstall all plugins and close down this Uppy instance.
   */


  close(_temp2) {
    let {
      reason
    } = _temp2 === void 0 ? {} : _temp2;
    this.log(`Closing Uppy instance ${this.opts.id}: removing all files and uninstalling plugins`);
    this.cancelAll({
      reason
    });

    _classPrivateFieldLooseBase(this, _storeUnsubscribe)[_storeUnsubscribe]();

    this.iteratePlugins(plugin => {
      this.removePlugin(plugin);
    });

    if (typeof window !== 'undefined' && window.removeEventListener) {
      window.removeEventListener('online', _classPrivateFieldLooseBase(this, _updateOnlineStatus)[_updateOnlineStatus]);
      window.removeEventListener('offline', _classPrivateFieldLooseBase(this, _updateOnlineStatus)[_updateOnlineStatus]);
    }
  }

  hideInfo() {
    const {
      info
    } = this.getState();
    this.setState({
      info: info.slice(1)
    });
    this.emit('info-hidden');
  }
  /**
   * Set info message in `state.info`, so that UI plugins like `Informer`
   * can display the message.
   *
   * @param {string | object} message Message to be displayed by the informer
   * @param {string} [type]
   * @param {number} [duration]
   */


  info(message, type, duration) {
    if (type === void 0) {
      type = 'info';
    }

    if (duration === void 0) {
      duration = 3000;
    }

    const isComplexMessage = typeof message === 'object';
    this.setState({
      info: [...this.getState().info, {
        type,
        message: isComplexMessage ? message.message : message,
        details: isComplexMessage ? message.details : null
      }]
    });
    setTimeout(() => this.hideInfo(), duration);
    this.emit('info-visible');
  }
  /**
   * Passes messages to a function, provided in `opts.logger`.
   * If `opts.logger: Uppy.debugLogger` or `opts.debug: true`, logs to the browser console.
   *
   * @param {string|object} message to log
   * @param {string} [type] optional `error` or `warning`
   */


  log(message, type) {
    const {
      logger
    } = this.opts;

    switch (type) {
      case 'error':
        logger.error(message);
        break;

      case 'warning':
        logger.warn(message);
        break;

      default:
        logger.debug(message);
        break;
    }
  }
  /**
   * Restore an upload by its ID.
   */


  restore(uploadID) {
    this.log(`Core: attempting to restore upload "${uploadID}"`);

    if (!this.getState().currentUploads[uploadID]) {
      _classPrivateFieldLooseBase(this, _removeUpload)[_removeUpload](uploadID);

      return Promise.reject(new Error('Nonexistent upload'));
    }

    return _classPrivateFieldLooseBase(this, _runUpload)[_runUpload](uploadID);
  }
  /**
   * Create an upload for a bunch of files.
   *
   * @param {Array<string>} fileIDs File IDs to include in this upload.
   * @returns {string} ID of this upload.
   */


  [_Symbol$for2]() {
    return _classPrivateFieldLooseBase(this, _createUpload)[_createUpload](...arguments);
  }

  /**
   * Add data to an upload's result object.
   *
   * @param {string} uploadID The ID of the upload.
   * @param {object} data Data properties to add to the result object.
   */
  addResultData(uploadID, data) {
    if (!_classPrivateFieldLooseBase(this, _getUpload)[_getUpload](uploadID)) {
      this.log(`Not setting result for an upload that has been removed: ${uploadID}`);
      return;
    }

    const {
      currentUploads
    } = this.getState();
    const currentUpload = { ...currentUploads[uploadID],
      result: { ...currentUploads[uploadID].result,
        ...data
      }
    };
    this.setState({
      currentUploads: { ...currentUploads,
        [uploadID]: currentUpload
      }
    });
  }
  /**
   * Remove an upload, eg. if it has been canceled or completed.
   *
   * @param {string} uploadID The ID of the upload.
   */


  /**
   * Start an upload for all the files that are not currently being uploaded.
   *
   * @returns {Promise}
   */
  upload() {
    var _classPrivateFieldLoo;

    if (!((_classPrivateFieldLoo = _classPrivateFieldLooseBase(this, _plugins)[_plugins].uploader) != null && _classPrivateFieldLoo.length)) {
      this.log('No uploader type plugins are used', 'warning');
    }

    let {
      files
    } = this.getState();
    const onBeforeUploadResult = this.opts.onBeforeUpload(files);

    if (onBeforeUploadResult === false) {
      return Promise.reject(new Error('Not starting the upload because onBeforeUpload returned false'));
    }

    if (onBeforeUploadResult && typeof onBeforeUploadResult === 'object') {
      files = onBeforeUploadResult; // Updating files in state, because uploader plugins receive file IDs,
      // and then fetch the actual file object from state

      this.setState({
        files
      });
    }

    return Promise.resolve().then(() => _classPrivateFieldLooseBase(this, _restricter)[_restricter].validateMinNumberOfFiles(files)).catch(err => {
      _classPrivateFieldLooseBase(this, _informAndEmit)[_informAndEmit]([err]);

      throw err;
    }).then(() => {
      if (!_classPrivateFieldLooseBase(this, _checkRequiredMetaFields)[_checkRequiredMetaFields](files)) {
        throw new _Restricter_js__WEBPACK_IMPORTED_MODULE_10__.RestrictionError(this.i18n('missingRequiredMetaField'));
      }
    }).catch(err => {
      // Doing this in a separate catch because we already emited and logged
      // all the errors in `checkRequiredMetaFields` so we only throw a generic
      // missing fields error here.
      throw err;
    }).then(() => {
      const {
        currentUploads
      } = this.getState(); // get a list of files that are currently assigned to uploads

      const currentlyUploadingFiles = Object.values(currentUploads).flatMap(curr => curr.fileIDs);
      const waitingFileIDs = [];
      Object.keys(files).forEach(fileID => {
        const file = this.getFile(fileID); // if the file hasn't started uploading and hasn't already been assigned to an upload..

        if (!file.progress.uploadStarted && currentlyUploadingFiles.indexOf(fileID) === -1) {
          waitingFileIDs.push(file.id);
        }
      });

      const uploadID = _classPrivateFieldLooseBase(this, _createUpload)[_createUpload](waitingFileIDs);

      return _classPrivateFieldLooseBase(this, _runUpload)[_runUpload](uploadID);
    }).catch(err => {
      this.emit('error', err);
      this.log(err, 'error');
      throw err;
    });
  }

}

function _informAndEmit2(errors) {
  for (const error of errors) {
    const {
      file,
      isRestriction
    } = error;

    if (isRestriction) {
      this.emit('restriction-failed', file, error);
    } else {
      this.emit('error', error);
    }

    this.log(error, 'warning');
  }

  const userFacingErrors = errors.filter(error => error.isUserFacing); // don't flood the user: only show the first 4 toasts

  const maxNumToShow = 4;
  const firstErrors = userFacingErrors.slice(0, maxNumToShow);
  const additionalErrors = userFacingErrors.slice(maxNumToShow);
  firstErrors.forEach(_ref3 => {
    let {
      message,
      details = ''
    } = _ref3;
    this.info({
      message,
      details
    }, 'error', this.opts.infoTimeout);
  });

  if (additionalErrors.length > 0) {
    this.info({
      message: this.i18n('additionalRestrictionsFailed', {
        count: additionalErrors.length
      })
    });
  }
}

function _checkRequiredMetaFieldsOnFile2(file) {
  const {
    missingFields,
    error
  } = _classPrivateFieldLooseBase(this, _restricter)[_restricter].getMissingRequiredMetaFields(file);

  if (missingFields.length > 0) {
    this.setFileState(file.id, {
      missingRequiredMetaFields: missingFields
    });
    this.log(error.message);
    this.emit('restriction-failed', file, error);
    return false;
  }

  return true;
}

function _checkRequiredMetaFields2(files) {
  let success = true;

  for (const file of Object.values(files)) {
    if (!_classPrivateFieldLooseBase(this, _checkRequiredMetaFieldsOnFile)[_checkRequiredMetaFieldsOnFile](file)) {
      success = false;
    }
  }

  return success;
}

function _assertNewUploadAllowed2(file) {
  const {
    allowNewUpload
  } = this.getState();

  if (allowNewUpload === false) {
    const error = new _Restricter_js__WEBPACK_IMPORTED_MODULE_10__.RestrictionError(this.i18n('noMoreFilesAllowed'), {
      file
    });

    _classPrivateFieldLooseBase(this, _informAndEmit)[_informAndEmit]([error]);

    throw error;
  }
}

function _transformFile2(fileDescriptorOrFile) {
  // Uppy expects files in { name, type, size, data } format.
  // If the actual File object is passed from input[type=file] or drag-drop,
  // we normalize it to match Uppy file object
  const fileDescriptor = fileDescriptorOrFile instanceof File ? {
    name: fileDescriptorOrFile.name,
    type: fileDescriptorOrFile.type,
    size: fileDescriptorOrFile.size,
    data: fileDescriptorOrFile
  } : fileDescriptorOrFile;
  const fileType = (0,_uppy_utils_lib_getFileType__WEBPACK_IMPORTED_MODULE_4__["default"])(fileDescriptor);
  const fileName = (0,_getFileName_js__WEBPACK_IMPORTED_MODULE_8__["default"])(fileType, fileDescriptor);
  const fileExtension = (0,_uppy_utils_lib_getFileNameAndExtension__WEBPACK_IMPORTED_MODULE_5__["default"])(fileName).extension;
  const isRemote = Boolean(fileDescriptor.isRemote);
  const id = (0,_uppy_utils_lib_generateFileID__WEBPACK_IMPORTED_MODULE_6__.getSafeFileId)(fileDescriptor);
  const meta = fileDescriptor.meta || {};
  meta.name = fileName;
  meta.type = fileType; // `null` means the size is unknown.

  const size = Number.isFinite(fileDescriptor.data.size) ? fileDescriptor.data.size : null;
  return {
    source: fileDescriptor.source || '',
    id,
    name: fileName,
    extension: fileExtension || '',
    meta: { ...this.getState().meta,
      ...meta
    },
    type: fileType,
    data: fileDescriptor.data,
    progress: {
      percentage: 0,
      bytesUploaded: 0,
      bytesTotal: size,
      uploadComplete: false,
      uploadStarted: null
    },
    size,
    isRemote,
    remote: fileDescriptor.remote || '',
    preview: fileDescriptor.preview
  };
}

function _startIfAutoProceed2() {
  if (this.opts.autoProceed && !this.scheduledAutoProceed) {
    this.scheduledAutoProceed = setTimeout(() => {
      this.scheduledAutoProceed = null;
      this.upload().catch(err => {
        if (!err.isRestriction) {
          this.log(err.stack || err.message || err);
        }
      });
    }, 4);
  }
}

function _checkAndUpdateFileState2(filesToAdd) {
  const {
    files: existingFiles
  } = this.getState(); // create a copy of the files object only once

  const nextFilesState = { ...existingFiles
  };
  const validFilesToAdd = [];
  const errors = [];

  for (const fileToAdd of filesToAdd) {
    try {
      var _existingFiles$newFil;

      let newFile = _classPrivateFieldLooseBase(this, _transformFile)[_transformFile](fileToAdd); // If a file has been recovered (Golden Retriever), but we were unable to recover its data (probably too large),
      // users are asked to re-select these half-recovered files and then this method will be called again.
      // In order to keep the progress, meta and everthing else, we keep the existing file,
      // but we replace `data`, and we remove `isGhost`, because the file is no longer a ghost now


      if ((_existingFiles$newFil = existingFiles[newFile.id]) != null && _existingFiles$newFil.isGhost) {
        const {
          isGhost,
          ...existingFileState
        } = existingFiles[newFile.id];
        newFile = { ...existingFileState,
          data: fileToAdd.data
        };
        this.log(`Replaced the blob in the restored ghost file: ${newFile.name}, ${newFile.id}`);
      }

      if (this.checkIfFileAlreadyExists(newFile.id)) {
        throw new _Restricter_js__WEBPACK_IMPORTED_MODULE_10__.RestrictionError(this.i18n('noDuplicates', {
          fileName: newFile.name
        }), {
          file: fileToAdd
        });
      }

      const onBeforeFileAddedResult = this.opts.onBeforeFileAdded(newFile, nextFilesState);

      if (onBeforeFileAddedResult === false) {
        // Don’t show UI info for this error, as it should be done by the developer
        throw new _Restricter_js__WEBPACK_IMPORTED_MODULE_10__.RestrictionError('Cannot add the file because onBeforeFileAdded returned false.', {
          isUserFacing: false,
          file: fileToAdd
        });
      } else if (typeof onBeforeFileAddedResult === 'object' && onBeforeFileAddedResult !== null) {
        newFile = onBeforeFileAddedResult;
      }

      _classPrivateFieldLooseBase(this, _restricter)[_restricter].validateSingleFile(newFile); // need to add it to the new local state immediately, so we can use the state to validate the next files too


      nextFilesState[newFile.id] = newFile;
      validFilesToAdd.push(newFile);
    } catch (err) {
      errors.push(err);
    }
  }

  try {
    // need to run this separately because it's much more slow, so if we run it inside the for-loop it will be very slow
    // when many files are added
    _classPrivateFieldLooseBase(this, _restricter)[_restricter].validateAggregateRestrictions(Object.values(existingFiles), validFilesToAdd);
  } catch (err) {
    errors.push(err); // If we have any aggregate error, don't allow adding this batch

    return {
      nextFilesState: existingFiles,
      validFilesToAdd: [],
      errors
    };
  }

  return {
    nextFilesState,
    validFilesToAdd,
    errors
  };
}

function _addListeners2() {
  /**
   * @param {Error} error
   * @param {object} [file]
   * @param {object} [response]
   */
  const errorHandler = (error, file, response) => {
    let errorMsg = error.message || 'Unknown error';

    if (error.details) {
      errorMsg += ` ${error.details}`;
    }

    this.setState({
      error: errorMsg
    });

    if (file != null && file.id in this.getState().files) {
      this.setFileState(file.id, {
        error: errorMsg,
        response
      });
    }
  };

  this.on('error', errorHandler);
  this.on('upload-error', (file, error, response) => {
    errorHandler(error, file, response);

    if (typeof error === 'object' && error.message) {
      const newError = new Error(error.message);
      newError.isUserFacing = true; // todo maybe don't do this with all errors?

      newError.details = error.message;

      if (error.details) {
        newError.details += ` ${error.details}`;
      }

      newError.message = this.i18n('failedToUpload', {
        file: file == null ? void 0 : file.name
      });

      _classPrivateFieldLooseBase(this, _informAndEmit)[_informAndEmit]([newError]);
    } else {
      _classPrivateFieldLooseBase(this, _informAndEmit)[_informAndEmit]([error]);
    }
  });
  let uploadStalledWarningRecentlyEmitted;
  this.on('upload-stalled', (error, files) => {
    const {
      message
    } = error;
    const details = files.map(file => file.meta.name).join(', ');

    if (!uploadStalledWarningRecentlyEmitted) {
      this.info({
        message,
        details
      }, 'warning', this.opts.infoTimeout);
      uploadStalledWarningRecentlyEmitted = setTimeout(() => {
        uploadStalledWarningRecentlyEmitted = null;
      }, this.opts.infoTimeout);
    }

    this.log(`${message} ${details}`.trim(), 'warning');
  });
  this.on('upload', () => {
    this.setState({
      error: null
    });
  });

  const onUploadStarted = files => {
    const filesFiltered = files.filter(file => {
      const exists = file != null && this.getFile(file.id);
      if (!exists) this.log(`Not setting progress for a file that has been removed: ${file == null ? void 0 : file.id}`);
      return exists;
    });
    const filesState = Object.fromEntries(filesFiltered.map(file => [file.id, {
      progress: {
        uploadStarted: Date.now(),
        uploadComplete: false,
        percentage: 0,
        bytesUploaded: 0,
        bytesTotal: file.size
      }
    }]));
    this.patchFilesState(filesState);
  };

  this.on('upload-start', files => {
    files.forEach(file => {
      // todo backward compat, remove this event in a next major
      this.emit('upload-started', file);
    });
    onUploadStarted(files);
  });
  this.on('upload-progress', this.calculateProgress);
  this.on('upload-success', (file, uploadResp) => {
    if (file == null || !this.getFile(file.id)) {
      this.log(`Not setting progress for a file that has been removed: ${file == null ? void 0 : file.id}`);
      return;
    }

    const currentProgress = this.getFile(file.id).progress;
    this.setFileState(file.id, {
      progress: { ...currentProgress,
        postprocess: _classPrivateFieldLooseBase(this, _postProcessors)[_postProcessors].size > 0 ? {
          mode: 'indeterminate'
        } : null,
        uploadComplete: true,
        percentage: 100,
        bytesUploaded: currentProgress.bytesTotal
      },
      response: uploadResp,
      uploadURL: uploadResp.uploadURL,
      isPaused: false
    }); // Remote providers sometimes don't tell us the file size,
    // but we can know how many bytes we uploaded once the upload is complete.

    if (file.size == null) {
      this.setFileState(file.id, {
        size: uploadResp.bytesUploaded || currentProgress.bytesTotal
      });
    }

    this.calculateTotalProgress();
  });
  this.on('preprocess-progress', (file, progress) => {
    if (file == null || !this.getFile(file.id)) {
      this.log(`Not setting progress for a file that has been removed: ${file == null ? void 0 : file.id}`);
      return;
    }

    this.setFileState(file.id, {
      progress: { ...this.getFile(file.id).progress,
        preprocess: progress
      }
    });
  });
  this.on('preprocess-complete', file => {
    if (file == null || !this.getFile(file.id)) {
      this.log(`Not setting progress for a file that has been removed: ${file == null ? void 0 : file.id}`);
      return;
    }

    const files = { ...this.getState().files
    };
    files[file.id] = { ...files[file.id],
      progress: { ...files[file.id].progress
      }
    };
    delete files[file.id].progress.preprocess;
    this.setState({
      files
    });
  });
  this.on('postprocess-progress', (file, progress) => {
    if (file == null || !this.getFile(file.id)) {
      this.log(`Not setting progress for a file that has been removed: ${file == null ? void 0 : file.id}`);
      return;
    }

    this.setFileState(file.id, {
      progress: { ...this.getState().files[file.id].progress,
        postprocess: progress
      }
    });
  });
  this.on('postprocess-complete', file => {
    if (file == null || !this.getFile(file.id)) {
      this.log(`Not setting progress for a file that has been removed: ${file == null ? void 0 : file.id}`);
      return;
    }

    const files = { ...this.getState().files
    };
    files[file.id] = { ...files[file.id],
      progress: { ...files[file.id].progress
      }
    };
    delete files[file.id].progress.postprocess;
    this.setState({
      files
    });
  });
  this.on('restored', () => {
    // Files may have changed--ensure progress is still accurate.
    this.calculateTotalProgress();
  });
  this.on('dashboard:file-edit-complete', file => {
    if (file) {
      _classPrivateFieldLooseBase(this, _checkRequiredMetaFieldsOnFile)[_checkRequiredMetaFieldsOnFile](file);
    }
  }); // show informer if offline

  if (typeof window !== 'undefined' && window.addEventListener) {
    window.addEventListener('online', _classPrivateFieldLooseBase(this, _updateOnlineStatus)[_updateOnlineStatus]);
    window.addEventListener('offline', _classPrivateFieldLooseBase(this, _updateOnlineStatus)[_updateOnlineStatus]);
    setTimeout(_classPrivateFieldLooseBase(this, _updateOnlineStatus)[_updateOnlineStatus], 3000);
  }
}

function _createUpload2(fileIDs, opts) {
  if (opts === void 0) {
    opts = {};
  }

  // uppy.retryAll sets this to true — when retrying we want to ignore `allowNewUpload: false`
  const {
    forceAllowNewUpload = false
  } = opts;
  const {
    allowNewUpload,
    currentUploads
  } = this.getState();

  if (!allowNewUpload && !forceAllowNewUpload) {
    throw new Error('Cannot create a new upload: already uploading.');
  }

  const uploadID = (0,nanoid_non_secure__WEBPACK_IMPORTED_MODULE_12__.nanoid)();
  this.emit('upload', {
    id: uploadID,
    fileIDs
  });
  this.setState({
    allowNewUpload: this.opts.allowMultipleUploadBatches !== false && this.opts.allowMultipleUploads !== false,
    currentUploads: { ...currentUploads,
      [uploadID]: {
        fileIDs,
        step: 0,
        result: {}
      }
    }
  });
  return uploadID;
}

function _getUpload2(uploadID) {
  const {
    currentUploads
  } = this.getState();
  return currentUploads[uploadID];
}

function _removeUpload2(uploadID) {
  const currentUploads = { ...this.getState().currentUploads
  };
  delete currentUploads[uploadID];
  this.setState({
    currentUploads
  });
}

async function _runUpload2(uploadID) {
  const getCurrentUpload = () => {
    const {
      currentUploads
    } = this.getState();
    return currentUploads[uploadID];
  };

  let currentUpload = getCurrentUpload();
  const steps = [..._classPrivateFieldLooseBase(this, _preProcessors)[_preProcessors], ..._classPrivateFieldLooseBase(this, _uploaders)[_uploaders], ..._classPrivateFieldLooseBase(this, _postProcessors)[_postProcessors]];

  try {
    for (let step = currentUpload.step || 0; step < steps.length; step++) {
      if (!currentUpload) {
        break;
      }

      const fn = steps[step];
      this.setState({
        currentUploads: { ...this.getState().currentUploads,
          [uploadID]: { ...currentUpload,
            step
          }
        }
      });
      const {
        fileIDs
      } = currentUpload; // TODO give this the `updatedUpload` object as its only parameter maybe?
      // Otherwise when more metadata may be added to the upload this would keep getting more parameters

      await fn(fileIDs, uploadID); // Update currentUpload value in case it was modified asynchronously.

      currentUpload = getCurrentUpload();
    }
  } catch (err) {
    _classPrivateFieldLooseBase(this, _removeUpload)[_removeUpload](uploadID);

    throw err;
  } // Set result data.


  if (currentUpload) {
    // Mark postprocessing step as complete if necessary; this addresses a case where we might get
    // stuck in the postprocessing UI while the upload is fully complete.
    // If the postprocessing steps do not do any work, they may not emit postprocessing events at
    // all, and never mark the postprocessing as complete. This is fine on its own but we
    // introduced code in the @uppy/core upload-success handler to prepare postprocessing progress
    // state if any postprocessors are registered. That is to avoid a "flash of completed state"
    // before the postprocessing plugins can emit events.
    //
    // So, just in case an upload with postprocessing plugins *has* completed *without* emitting
    // postprocessing completion, we do it instead.
    currentUpload.fileIDs.forEach(fileID => {
      const file = this.getFile(fileID);

      if (file && file.progress.postprocess) {
        this.emit('postprocess-complete', file);
      }
    });
    const files = currentUpload.fileIDs.map(fileID => this.getFile(fileID));
    const successful = files.filter(file => !file.error);
    const failed = files.filter(file => file.error);
    await this.addResultData(uploadID, {
      successful,
      failed,
      uploadID
    }); // Update currentUpload value in case it was modified asynchronously.

    currentUpload = getCurrentUpload();
  } // Emit completion events.
  // This is in a separate function so that the `currentUploads` variable
  // always refers to the latest state. In the handler right above it refers
  // to an outdated object without the `.result` property.


  let result;

  if (currentUpload) {
    result = currentUpload.result;
    this.emit('complete', result);

    _classPrivateFieldLooseBase(this, _removeUpload)[_removeUpload](uploadID);
  }

  if (result == null) {
    this.log(`Not setting result for an upload that has been removed: ${uploadID}`);
  }

  return result;
}

Uppy.VERSION = packageJson.version;
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Uppy);

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/core/lib/getFileName.js":
/*!********************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/core/lib/getFileName.js ***!
  \********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getFileName)
/* harmony export */ });
function getFileName(fileType, fileDescriptor) {
  if (fileDescriptor.name) {
    return fileDescriptor.name;
  }

  if (fileType.split('/')[0] === 'image') {
    return `${fileType.split('/')[0]}.${fileType.split('/')[1]}`;
  }

  return 'noname';
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/core/lib/index.js":
/*!**************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/core/lib/index.js ***!
  \**************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   BasePlugin: () => (/* reexport safe */ _BasePlugin_js__WEBPACK_IMPORTED_MODULE_2__["default"]),
/* harmony export */   UIPlugin: () => (/* reexport safe */ _UIPlugin_js__WEBPACK_IMPORTED_MODULE_1__["default"]),
/* harmony export */   Uppy: () => (/* reexport safe */ _Uppy_js__WEBPACK_IMPORTED_MODULE_0__["default"]),
/* harmony export */   debugLogger: () => (/* reexport safe */ _loggers_js__WEBPACK_IMPORTED_MODULE_3__.debugLogger),
/* harmony export */   "default": () => (/* reexport safe */ _Uppy_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _Uppy_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Uppy.js */ "./src/jgDashboard/node_modules/@uppy/core/lib/Uppy.js");
/* harmony import */ var _UIPlugin_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UIPlugin.js */ "./src/jgDashboard/node_modules/@uppy/core/lib/UIPlugin.js");
/* harmony import */ var _BasePlugin_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./BasePlugin.js */ "./src/jgDashboard/node_modules/@uppy/core/lib/BasePlugin.js");
/* harmony import */ var _loggers_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./loggers.js */ "./src/jgDashboard/node_modules/@uppy/core/lib/loggers.js");






/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/core/lib/locale.js":
/*!***************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/core/lib/locale.js ***!
  \***************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  strings: {
    addBulkFilesFailed: {
      0: 'Failed to add %{smart_count} file due to an internal error',
      1: 'Failed to add %{smart_count} files due to internal errors'
    },
    youCanOnlyUploadX: {
      0: 'You can only upload %{smart_count} file',
      1: 'You can only upload %{smart_count} files'
    },
    youHaveToAtLeastSelectX: {
      0: 'You have to select at least %{smart_count} file',
      1: 'You have to select at least %{smart_count} files'
    },
    exceedsSize: '%{file} exceeds maximum allowed size of %{size}',
    missingRequiredMetaField: 'Missing required meta fields',
    missingRequiredMetaFieldOnFile: 'Missing required meta fields in %{fileName}',
    inferiorSize: 'This file is smaller than the allowed size of %{size}',
    youCanOnlyUploadFileTypes: 'You can only upload: %{types}',
    noMoreFilesAllowed: 'Cannot add more files',
    noDuplicates: "Cannot add the duplicate file '%{fileName}', it already exists",
    companionError: 'Connection with Companion failed',
    authAborted: 'Authentication aborted',
    companionUnauthorizeHint: 'To unauthorize to your %{provider} account, please go to %{url}',
    failedToUpload: 'Failed to upload %{file}',
    noInternetConnection: 'No Internet connection',
    connectedToInternet: 'Connected to the Internet',
    // Strings for remote providers
    noFilesFound: 'You have no files or folders here',
    noSearchResults: 'Unfortunately, there are no results for this search',
    selectX: {
      0: 'Select %{smart_count}',
      1: 'Select %{smart_count}'
    },
    allFilesFromFolderNamed: 'All files from folder %{name}',
    openFolderNamed: 'Open folder %{name}',
    cancel: 'Cancel',
    logOut: 'Log out',
    filter: 'Filter',
    resetFilter: 'Reset filter',
    loading: 'Loading...',
    authenticateWithTitle: 'Please authenticate with %{pluginName} to select files',
    authenticateWith: 'Connect to %{pluginName}',
    signInWithGoogle: 'Sign in with Google',
    searchImages: 'Search for images',
    enterTextToSearch: 'Enter text to search for images',
    search: 'Search',
    resetSearch: 'Reset search',
    emptyFolderAdded: 'No files were added from empty folder',
    addedNumFiles: 'Added %{numFiles} file(s)',
    folderAlreadyAdded: 'The folder "%{folder}" was already added',
    folderAdded: {
      0: 'Added %{smart_count} file from %{folder}',
      1: 'Added %{smart_count} files from %{folder}'
    },
    additionalRestrictionsFailed: '%{count} additional restrictions were not fulfilled'
  }
});

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/core/lib/loggers.js":
/*!****************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/core/lib/loggers.js ***!
  \****************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   debugLogger: () => (/* binding */ debugLogger),
/* harmony export */   justErrorsLogger: () => (/* binding */ justErrorsLogger)
/* harmony export */ });
/* harmony import */ var _uppy_utils_lib_getTimeStamp__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @uppy/utils/lib/getTimeStamp */ "./src/jgDashboard/node_modules/@uppy/utils/lib/getTimeStamp.js");
/* eslint-disable no-console */
 // Swallow all logs, except errors.
// default if logger is not set or debug: false

const justErrorsLogger = {
  debug: () => {},
  warn: () => {},
  error: function () {
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    return console.error(`[Uppy] [${(0,_uppy_utils_lib_getTimeStamp__WEBPACK_IMPORTED_MODULE_0__["default"])()}]`, ...args);
  }
}; // Print logs to console with namespace + timestamp,
// set by logger: Uppy.debugLogger or debug: true

const debugLogger = {
  debug: function () {
    for (var _len2 = arguments.length, args = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
      args[_key2] = arguments[_key2];
    }

    return console.debug(`[Uppy] [${(0,_uppy_utils_lib_getTimeStamp__WEBPACK_IMPORTED_MODULE_0__["default"])()}]`, ...args);
  },
  warn: function () {
    for (var _len3 = arguments.length, args = new Array(_len3), _key3 = 0; _key3 < _len3; _key3++) {
      args[_key3] = arguments[_key3];
    }

    return console.warn(`[Uppy] [${(0,_uppy_utils_lib_getTimeStamp__WEBPACK_IMPORTED_MODULE_0__["default"])()}]`, ...args);
  },
  error: function () {
    for (var _len4 = arguments.length, args = new Array(_len4), _key4 = 0; _key4 < _len4; _key4++) {
      args[_key4] = arguments[_key4];
    }

    return console.error(`[Uppy] [${(0,_uppy_utils_lib_getTimeStamp__WEBPACK_IMPORTED_MODULE_0__["default"])()}]`, ...args);
  }
};


/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/core/lib/supportsUploadProgress.js":
/*!*******************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/core/lib/supportsUploadProgress.js ***!
  \*******************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ supportsUploadProgress)
/* harmony export */ });
// Edge 15.x does not fire 'progress' events on uploads.
// See https://github.com/transloadit/uppy/issues/945
// And https://developer.microsoft.com/en-us/microsoft-edge/platform/issues/12224510/
function supportsUploadProgress(userAgent) {
  // Allow passing in userAgent for tests
  if (userAgent == null && typeof navigator !== 'undefined') {
    // eslint-disable-next-line no-param-reassign
    userAgent = navigator.userAgent;
  } // Assume it works because basically everything supports progress events.


  if (!userAgent) return true;
  const m = /Edge\/(\d+\.\d+)/.exec(userAgent);
  if (!m) return true;
  const edgeVersion = m[1];
  let [major, minor] = edgeVersion.split('.');
  major = parseInt(major, 10);
  minor = parseInt(minor, 10); // Worked before:
  // Edge 40.15063.0.0
  // Microsoft EdgeHTML 15.15063

  if (major < 15 || major === 15 && minor < 15063) {
    return true;
  } // Fixed in:
  // Microsoft EdgeHTML 18.18218


  if (major > 18 || major === 18 && minor >= 18218) {
    return true;
  } // other versions don't work.


  return false;
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/Dashboard.js":
/*!***********************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/Dashboard.js ***!
  \***********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Dashboard)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var _uppy_core__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @uppy/core */ "./src/jgDashboard/node_modules/@uppy/core/lib/index.js");
/* harmony import */ var _uppy_status_bar__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @uppy/status-bar */ "./src/jgDashboard/node_modules/@uppy/status-bar/lib/index.js");
/* harmony import */ var _uppy_informer__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @uppy/informer */ "./src/jgDashboard/node_modules/@uppy/informer/lib/index.js");
/* harmony import */ var _uppy_thumbnail_generator__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @uppy/thumbnail-generator */ "./src/jgDashboard/node_modules/@uppy/thumbnail-generator/lib/index.js");
/* harmony import */ var _uppy_utils_lib_findAllDOMElements__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @uppy/utils/lib/findAllDOMElements */ "./src/jgDashboard/node_modules/@uppy/utils/lib/findAllDOMElements.js");
/* harmony import */ var _uppy_utils_lib_toArray__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @uppy/utils/lib/toArray */ "./src/jgDashboard/node_modules/@uppy/utils/lib/toArray.js");
/* harmony import */ var _uppy_utils_lib_getDroppedFiles__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @uppy/utils/lib/getDroppedFiles */ "./src/jgDashboard/node_modules/@uppy/utils/lib/getDroppedFiles/index.js");
/* harmony import */ var nanoid_non_secure__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! nanoid/non-secure */ "./src/jgDashboard/node_modules/nanoid/non-secure/index.js");
/* harmony import */ var memoize_one__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! memoize-one */ "./src/jgDashboard/node_modules/memoize-one/dist/memoize-one.esm.js");
/* harmony import */ var _utils_trapFocus_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./utils/trapFocus.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/trapFocus.js");
/* harmony import */ var _utils_createSuperFocus_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./utils/createSuperFocus.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/createSuperFocus.js");
/* harmony import */ var _components_Dashboard_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./components/Dashboard.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/Dashboard.js");
/* harmony import */ var _locale_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./locale.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/locale.js");
function _classPrivateFieldLooseBase(receiver, privateKey) { if (!Object.prototype.hasOwnProperty.call(receiver, privateKey)) { throw new TypeError("attempted to use private field on non-instance"); } return receiver; }

var id = 0;

function _classPrivateFieldLooseKey(name) { return "__private_" + id++ + "_" + name; }














const packageJson = {
  "version": "3.4.0"
};

const memoize = memoize_one__WEBPACK_IMPORTED_MODULE_12__["default"]["default"] || memoize_one__WEBPACK_IMPORTED_MODULE_12__["default"];
const TAB_KEY = 9;
const ESC_KEY = 27;

function createPromise() {
  const o = {};
  o.promise = new Promise((resolve, reject) => {
    o.resolve = resolve;
    o.reject = reject;
  });
  return o;
}

function defaultPickerIcon() {
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    "aria-hidden": "true",
    focusable: "false",
    width: "30",
    height: "30",
    viewBox: "0 0 30 30"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    d: "M15 30c8.284 0 15-6.716 15-15 0-8.284-6.716-15-15-15C6.716 0 0 6.716 0 15c0 8.284 6.716 15 15 15zm4.258-12.676v6.846h-8.426v-6.846H5.204l9.82-12.364 9.82 12.364H19.26z"
  }));
}
/**
 * Dashboard UI with previews, metadata editing, tabs for various services and more
 */


var _disabledNodes = /*#__PURE__*/_classPrivateFieldLooseKey("disabledNodes");

var _generateLargeThumbnailIfSingleFile = /*#__PURE__*/_classPrivateFieldLooseKey("generateLargeThumbnailIfSingleFile");

var _openFileEditorWhenFilesAdded = /*#__PURE__*/_classPrivateFieldLooseKey("openFileEditorWhenFilesAdded");

var _attachRenderFunctionToTarget = /*#__PURE__*/_classPrivateFieldLooseKey("attachRenderFunctionToTarget");

var _isTargetSupported = /*#__PURE__*/_classPrivateFieldLooseKey("isTargetSupported");

var _getAcquirers = /*#__PURE__*/_classPrivateFieldLooseKey("getAcquirers");

var _getProgressIndicators = /*#__PURE__*/_classPrivateFieldLooseKey("getProgressIndicators");

var _getEditors = /*#__PURE__*/_classPrivateFieldLooseKey("getEditors");

class Dashboard extends _uppy_core__WEBPACK_IMPORTED_MODULE_1__.UIPlugin {
  constructor(uppy, _opts) {
    var _this;

    super(uppy, _opts);
    _this = this;
    Object.defineProperty(this, _disabledNodes, {
      writable: true,
      value: null
    });

    this.removeTarget = plugin => {
      const pluginState = this.getPluginState(); // filter out the one we want to remove

      const newTargets = pluginState.targets.filter(target => target.id !== plugin.id);
      this.setPluginState({
        targets: newTargets
      });
    };

    this.addTarget = plugin => {
      const callerPluginId = plugin.id || plugin.constructor.name;
      const callerPluginName = plugin.title || callerPluginId;
      const callerPluginType = plugin.type;

      if (callerPluginType !== 'acquirer' && callerPluginType !== 'progressindicator' && callerPluginType !== 'editor') {
        const msg = 'Dashboard: can only be targeted by plugins of types: acquirer, progressindicator, editor';
        this.uppy.log(msg, 'error');
        return undefined;
      }

      const target = {
        id: callerPluginId,
        name: callerPluginName,
        type: callerPluginType
      };
      const state = this.getPluginState();
      const newTargets = state.targets.slice();
      newTargets.push(target);
      this.setPluginState({
        targets: newTargets
      });
      return this.el;
    };

    this.hideAllPanels = () => {
      const state = this.getPluginState();
      const update = {
        activePickerPanel: false,
        showAddFilesPanel: false,
        activeOverlayType: null,
        fileCardFor: null,
        showFileEditor: false
      };

      if (state.activePickerPanel === update.activePickerPanel && state.showAddFilesPanel === update.showAddFilesPanel && state.showFileEditor === update.showFileEditor && state.activeOverlayType === update.activeOverlayType) {
        // avoid doing a state update if nothing changed
        return;
      }

      this.setPluginState(update);
    };

    this.showPanel = id => {
      const {
        targets
      } = this.getPluginState();
      const activePickerPanel = targets.filter(target => {
        return target.type === 'acquirer' && target.id === id;
      })[0];
      this.setPluginState({
        activePickerPanel,
        activeOverlayType: 'PickerPanel'
      });
      this.uppy.emit('dashboard:show-panel', id);
    };

    this.canEditFile = file => {
      const {
        targets
      } = this.getPluginState();

      const editors = _classPrivateFieldLooseBase(this, _getEditors)[_getEditors](targets);

      return editors.some(target => this.uppy.getPlugin(target.id).canEditFile(file));
    };

    this.openFileEditor = file => {
      const {
        targets
      } = this.getPluginState();

      const editors = _classPrivateFieldLooseBase(this, _getEditors)[_getEditors](targets);

      this.setPluginState({
        showFileEditor: true,
        fileCardFor: file.id || null,
        activeOverlayType: 'FileEditor'
      });
      editors.forEach(editor => {
        this.uppy.getPlugin(editor.id).selectFile(file);
      });
    };

    this.saveFileEditor = () => {
      const {
        targets
      } = this.getPluginState();

      const editors = _classPrivateFieldLooseBase(this, _getEditors)[_getEditors](targets);

      editors.forEach(editor => {
        this.uppy.getPlugin(editor.id).save();
      });
      this.hideAllPanels();
    };

    this.openModal = () => {
      const {
        promise,
        resolve
      } = createPromise(); // save scroll position

      this.savedScrollPosition = window.pageYOffset; // save active element, so we can restore focus when modal is closed

      this.savedActiveElement = document.activeElement;

      if (this.opts.disablePageScrollWhenModalOpen) {
        document.body.classList.add('uppy-Dashboard-isFixed');
      }

      if (this.opts.animateOpenClose && this.getPluginState().isClosing) {
        const handler = () => {
          this.setPluginState({
            isHidden: false
          });
          this.el.removeEventListener('animationend', handler, false);
          resolve();
        };

        this.el.addEventListener('animationend', handler, false);
      } else {
        this.setPluginState({
          isHidden: false
        });
        resolve();
      }

      if (this.opts.browserBackButtonClose) {
        this.updateBrowserHistory();
      } // handle ESC and TAB keys in modal dialog


      document.addEventListener('keydown', this.handleKeyDownInModal);
      this.uppy.emit('dashboard:modal-open');
      return promise;
    };

    this.closeModal = function (opts) {
      if (opts === void 0) {
        opts = {};
      }

      const {
        // Whether the modal is being closed by the user (`true`) or by other means (e.g. browser back button)
        manualClose = true
      } = opts;

      const {
        isHidden,
        isClosing
      } = _this.getPluginState();

      if (isHidden || isClosing) {
        // short-circuit if animation is ongoing
        return undefined;
      }

      const {
        promise,
        resolve
      } = createPromise();

      if (_this.opts.disablePageScrollWhenModalOpen) {
        document.body.classList.remove('uppy-Dashboard-isFixed');
      }

      if (_this.opts.animateOpenClose) {
        _this.setPluginState({
          isClosing: true
        });

        const handler = () => {
          _this.setPluginState({
            isHidden: true,
            isClosing: false
          });

          _this.superFocus.cancel();

          _this.savedActiveElement.focus();

          _this.el.removeEventListener('animationend', handler, false);

          resolve();
        };

        _this.el.addEventListener('animationend', handler, false);
      } else {
        _this.setPluginState({
          isHidden: true
        });

        _this.superFocus.cancel();

        _this.savedActiveElement.focus();

        resolve();
      } // handle ESC and TAB keys in modal dialog


      document.removeEventListener('keydown', _this.handleKeyDownInModal);

      if (manualClose) {
        if (_this.opts.browserBackButtonClose) {
          var _history$state;

          // Make sure that the latest entry in the history state is our modal name
          // eslint-disable-next-line no-restricted-globals
          if ((_history$state = history.state) != null && _history$state[_this.modalName]) {
            // Go back in history to clear out the entry we created (ultimately closing the modal)
            // eslint-disable-next-line no-restricted-globals
            history.back();
          }
        }
      }

      _this.uppy.emit('dashboard:modal-closed');

      return promise;
    };

    this.isModalOpen = () => {
      return !this.getPluginState().isHidden || false;
    };

    this.requestCloseModal = () => {
      if (this.opts.onRequestCloseModal) {
        return this.opts.onRequestCloseModal();
      }

      return this.closeModal();
    };

    this.setDarkModeCapability = isDarkModeOn => {
      const {
        capabilities
      } = this.uppy.getState();
      this.uppy.setState({
        capabilities: { ...capabilities,
          darkMode: isDarkModeOn
        }
      });
    };

    this.handleSystemDarkModeChange = event => {
      const isDarkModeOnNow = event.matches;
      this.uppy.log(`[Dashboard] Dark mode is ${isDarkModeOnNow ? 'on' : 'off'}`);
      this.setDarkModeCapability(isDarkModeOnNow);
    };

    this.toggleFileCard = (show, fileID) => {
      const file = this.uppy.getFile(fileID);

      if (show) {
        this.uppy.emit('dashboard:file-edit-start', file);
      } else {
        this.uppy.emit('dashboard:file-edit-complete', file);
      }

      this.setPluginState({
        fileCardFor: show ? fileID : null,
        activeOverlayType: show ? 'FileCard' : null
      });
    };

    this.toggleAddFilesPanel = show => {
      this.setPluginState({
        showAddFilesPanel: show,
        activeOverlayType: show ? 'AddFiles' : null
      });
    };

    this.addFiles = files => {
      const descriptors = files.map(file => ({
        source: this.id,
        name: file.name,
        type: file.type,
        data: file,
        meta: {
          // path of the file relative to the ancestor directory the user selected.
          // e.g. 'docs/Old Prague/airbnb.pdf'
          relativePath: file.relativePath || file.webkitRelativePath || null
        }
      }));

      try {
        this.uppy.addFiles(descriptors);
      } catch (err) {
        this.uppy.log(err);
      }
    };

    this.startListeningToResize = () => {
      // Watch for Dashboard container (`.uppy-Dashboard-inner`) resize
      // and update containerWidth/containerHeight in plugin state accordingly.
      // Emits first event on initialization.
      this.resizeObserver = new ResizeObserver(entries => {
        const uppyDashboardInnerEl = entries[0];
        const {
          width,
          height
        } = uppyDashboardInnerEl.contentRect;
        this.setPluginState({
          containerWidth: width,
          containerHeight: height,
          areInsidesReadyToBeVisible: true
        });
      });
      this.resizeObserver.observe(this.el.querySelector('.uppy-Dashboard-inner')); // If ResizeObserver fails to emit an event telling us what size to use - default to the mobile view

      this.makeDashboardInsidesVisibleAnywayTimeout = setTimeout(() => {
        const pluginState = this.getPluginState();
        const isModalAndClosed = !this.opts.inline && pluginState.isHidden;

        if ( // We might want to enable this in the future
        // if ResizeObserver hasn't yet fired,
        !pluginState.areInsidesReadyToBeVisible // and it's not due to the modal being closed
        && !isModalAndClosed) {
          this.uppy.log('[Dashboard] resize event didn’t fire on time: defaulted to mobile layout', 'warning');
          this.setPluginState({
            areInsidesReadyToBeVisible: true
          });
        }
      }, 1000);
    };

    this.stopListeningToResize = () => {
      this.resizeObserver.disconnect();
      clearTimeout(this.makeDashboardInsidesVisibleAnywayTimeout);
    };

    this.recordIfFocusedOnUppyRecently = event => {
      if (this.el.contains(event.target)) {
        this.ifFocusedOnUppyRecently = true;
      } else {
        this.ifFocusedOnUppyRecently = false; // ___Why run this.superFocus.cancel here when it already runs in superFocusOnEachUpdate?
        //    Because superFocus is debounced, when we move from Uppy to some other element on the page,
        //    previously run superFocus sometimes hits and moves focus back to Uppy.

        this.superFocus.cancel();
      }
    };

    this.disableInteractiveElements = disable => {
      var _classPrivateFieldLoo;

      const NODES_TO_DISABLE = ['a[href]', 'input:not([disabled])', 'select:not([disabled])', 'textarea:not([disabled])', 'button:not([disabled])', '[role="button"]:not([disabled])'];
      const nodesToDisable = (_classPrivateFieldLoo = _classPrivateFieldLooseBase(this, _disabledNodes)[_disabledNodes]) != null ? _classPrivateFieldLoo : (0,_uppy_utils_lib_toArray__WEBPACK_IMPORTED_MODULE_6__["default"])(this.el.querySelectorAll(NODES_TO_DISABLE)).filter(node => !node.classList.contains('uppy-Dashboard-close'));

      for (const node of nodesToDisable) {
        // Links can’t have `disabled` attr, so we use `aria-disabled` for a11y
        if (node.tagName === 'A') {
          node.setAttribute('aria-disabled', disable);
        } else {
          node.disabled = disable;
        }
      }

      if (disable) {
        _classPrivateFieldLooseBase(this, _disabledNodes)[_disabledNodes] = nodesToDisable;
      } else {
        _classPrivateFieldLooseBase(this, _disabledNodes)[_disabledNodes] = null;
      }

      this.dashboardIsDisabled = disable;
    };

    this.updateBrowserHistory = () => {
      var _history$state2;

      // Ensure history state does not already contain our modal name to avoid double-pushing
      // eslint-disable-next-line no-restricted-globals
      if (!((_history$state2 = history.state) != null && _history$state2[this.modalName])) {
        // Push to history so that the page is not lost on browser back button press
        // eslint-disable-next-line no-restricted-globals
        history.pushState({ // eslint-disable-next-line no-restricted-globals
          ...history.state,
          [this.modalName]: true
        }, '');
      } // Listen for back button presses


      window.addEventListener('popstate', this.handlePopState, false);
    };

    this.handlePopState = event => {
      var _event$state;

      // Close the modal if the history state no longer contains our modal name
      if (this.isModalOpen() && (!event.state || !event.state[this.modalName])) {
        this.closeModal({
          manualClose: false
        });
      } // When the browser back button is pressed and uppy is now the latest entry
      // in the history but the modal is closed, fix the history by removing the
      // uppy history entry.
      // This occurs when another entry is added into the history state while the
      // modal is open, and then the modal gets manually closed.
      // Solves PR #575 (https://github.com/transloadit/uppy/pull/575)


      if (!this.isModalOpen() && (_event$state = event.state) != null && _event$state[this.modalName]) {
        // eslint-disable-next-line no-restricted-globals
        history.back();
      }
    };

    this.handleKeyDownInModal = event => {
      // close modal on esc key press
      if (event.keyCode === ESC_KEY) this.requestCloseModal(event); // trap focus on tab key press

      if (event.keyCode === TAB_KEY) _utils_trapFocus_js__WEBPACK_IMPORTED_MODULE_8__.forModal(event, this.getPluginState().activeOverlayType, this.el);
    };

    this.handleClickOutside = () => {
      if (this.opts.closeModalOnClickOutside) this.requestCloseModal();
    };

    this.handlePaste = event => {
      // Let any acquirer plugin (Url/Webcam/etc.) handle pastes to the root
      this.uppy.iteratePlugins(plugin => {
        if (plugin.type === 'acquirer') {
          // Every Plugin with .type acquirer can define handleRootPaste(event)
          plugin.handleRootPaste == null ? void 0 : plugin.handleRootPaste(event);
        }
      }); // Add all dropped files

      const files = (0,_uppy_utils_lib_toArray__WEBPACK_IMPORTED_MODULE_6__["default"])(event.clipboardData.files);

      if (files.length > 0) {
        this.uppy.log('[Dashboard] Files pasted');
        this.addFiles(files);
      }
    };

    this.handleInputChange = event => {
      event.preventDefault();
      const files = (0,_uppy_utils_lib_toArray__WEBPACK_IMPORTED_MODULE_6__["default"])(event.target.files);

      if (files.length > 0) {
        this.uppy.log('[Dashboard] Files selected through input');
        this.addFiles(files);
      }
    };

    this.handleDragOver = event => {
      var _this$opts$onDragOver, _this$opts;

      event.preventDefault();
      event.stopPropagation(); // Check if some plugin can handle the datatransfer without files —
      // for instance, the Url plugin can import a url

      const canSomePluginHandleRootDrop = () => {
        let somePluginCanHandleRootDrop = true;
        this.uppy.iteratePlugins(plugin => {
          if (plugin.canHandleRootDrop != null && plugin.canHandleRootDrop(event)) {
            somePluginCanHandleRootDrop = true;
          }
        });
        return somePluginCanHandleRootDrop;
      }; // Check if the "type" of the datatransfer object includes files


      const doesEventHaveFiles = () => {
        const {
          types
        } = event.dataTransfer;
        return types.some(type => type === 'Files');
      }; // Deny drop, if no plugins can handle datatransfer, there are no files,
      // or when opts.disabled is set, or new uploads are not allowed


      const somePluginCanHandleRootDrop = canSomePluginHandleRootDrop(event);
      const hasFiles = doesEventHaveFiles(event);

      if (!somePluginCanHandleRootDrop && !hasFiles || this.opts.disabled // opts.disableLocalFiles should only be taken into account if no plugins
      // can handle the datatransfer
      || this.opts.disableLocalFiles && (hasFiles || !somePluginCanHandleRootDrop) || !this.uppy.getState().allowNewUpload) {
        event.dataTransfer.dropEffect = 'none'; // eslint-disable-line no-param-reassign

        clearTimeout(this.removeDragOverClassTimeout);
        return;
      } // Add a small (+) icon on drop
      // (and prevent browsers from interpreting this as files being _moved_ into the
      // browser, https://github.com/transloadit/uppy/issues/1978).


      event.dataTransfer.dropEffect = 'copy'; // eslint-disable-line no-param-reassign

      clearTimeout(this.removeDragOverClassTimeout);
      this.setPluginState({
        isDraggingOver: true
      });
      (_this$opts$onDragOver = (_this$opts = this.opts).onDragOver) == null ? void 0 : _this$opts$onDragOver.call(_this$opts, event);
    };

    this.handleDragLeave = event => {
      var _this$opts$onDragLeav, _this$opts2;

      event.preventDefault();
      event.stopPropagation();
      clearTimeout(this.removeDragOverClassTimeout); // Timeout against flickering, this solution is taken from drag-drop library.
      // Solution with 'pointer-events: none' didn't work across browsers.

      this.removeDragOverClassTimeout = setTimeout(() => {
        this.setPluginState({
          isDraggingOver: false
        });
      }, 50);
      (_this$opts$onDragLeav = (_this$opts2 = this.opts).onDragLeave) == null ? void 0 : _this$opts$onDragLeav.call(_this$opts2, event);
    };

    this.handleDrop = async event => {
      var _this$opts$onDrop, _this$opts3;

      event.preventDefault();
      event.stopPropagation();
      clearTimeout(this.removeDragOverClassTimeout);
      this.setPluginState({
        isDraggingOver: false
      }); // Let any acquirer plugin (Url/Webcam/etc.) handle drops to the root

      this.uppy.iteratePlugins(plugin => {
        if (plugin.type === 'acquirer') {
          // Every Plugin with .type acquirer can define handleRootDrop(event)
          plugin.handleRootDrop == null ? void 0 : plugin.handleRootDrop(event);
        }
      }); // Add all dropped files

      let executedDropErrorOnce = false;

      const logDropError = error => {
        this.uppy.log(error, 'error'); // In practice all drop errors are most likely the same,
        // so let's just show one to avoid overwhelming the user

        if (!executedDropErrorOnce) {
          this.uppy.info(error.message, 'error');
          executedDropErrorOnce = true;
        }
      };

      this.uppy.log('[Dashboard] Processing dropped files'); // Add all dropped files

      const files = await (0,_uppy_utils_lib_getDroppedFiles__WEBPACK_IMPORTED_MODULE_7__["default"])(event.dataTransfer, {
        logDropError
      });

      if (files.length > 0) {
        this.uppy.log('[Dashboard] Files dropped');
        this.addFiles(files);
      }

      (_this$opts$onDrop = (_this$opts3 = this.opts).onDrop) == null ? void 0 : _this$opts$onDrop.call(_this$opts3, event);
    };

    this.handleRequestThumbnail = file => {
      if (!this.opts.waitForThumbnailsBeforeUpload) {
        this.uppy.emit('thumbnail:request', file);
      }
    };

    this.handleCancelThumbnail = file => {
      if (!this.opts.waitForThumbnailsBeforeUpload) {
        this.uppy.emit('thumbnail:cancel', file);
      }
    };

    this.handleKeyDownInInline = event => {
      // Trap focus on tab key press.
      if (event.keyCode === TAB_KEY) _utils_trapFocus_js__WEBPACK_IMPORTED_MODULE_8__.forInline(event, this.getPluginState().activeOverlayType, this.el);
    };

    this.handlePasteOnBody = event => {
      const isFocusInOverlay = this.el.contains(document.activeElement);

      if (isFocusInOverlay) {
        this.handlePaste(event);
      }
    };

    this.handleComplete = _ref => {
      let {
        failed
      } = _ref;

      if (this.opts.closeAfterFinish && failed.length === 0) {
        // All uploads are done
        this.requestCloseModal();
      }
    };

    this.handleCancelRestore = () => {
      this.uppy.emit('restore-canceled');
    };

    Object.defineProperty(this, _generateLargeThumbnailIfSingleFile, {
      writable: true,
      value: () => {
        if (this.opts.disableThumbnailGenerator) {
          return;
        }

        const LARGE_THUMBNAIL = 600;
        const files = this.uppy.getFiles();

        if (files.length === 1) {
          const thumbnailGenerator = this.uppy.getPlugin(`${this.id}:ThumbnailGenerator`);
          thumbnailGenerator == null ? void 0 : thumbnailGenerator.setOptions({
            thumbnailWidth: LARGE_THUMBNAIL
          });
          const fileForThumbnail = { ...files[0],
            preview: undefined
          };
          thumbnailGenerator.requestThumbnail(fileForThumbnail).then(() => {
            thumbnailGenerator == null ? void 0 : thumbnailGenerator.setOptions({
              thumbnailWidth: this.opts.thumbnailWidth
            });
          });
        }
      }
    });
    Object.defineProperty(this, _openFileEditorWhenFilesAdded, {
      writable: true,
      value: files => {
        const firstFile = files[0];

        if (this.canEditFile(firstFile)) {
          this.openFileEditor(firstFile);
        }
      }
    });

    this.initEvents = () => {
      // Modal open button
      if (this.opts.trigger && !this.opts.inline) {
        const showModalTrigger = (0,_uppy_utils_lib_findAllDOMElements__WEBPACK_IMPORTED_MODULE_5__["default"])(this.opts.trigger);

        if (showModalTrigger) {
          showModalTrigger.forEach(trigger => trigger.addEventListener('click', this.openModal));
        } else {
          this.uppy.log('Dashboard modal trigger not found. Make sure `trigger` is set in Dashboard options, unless you are planning to call `dashboard.openModal()` method yourself', 'warning');
        }
      }

      this.startListeningToResize();
      document.addEventListener('paste', this.handlePasteOnBody);
      this.uppy.on('plugin-remove', this.removeTarget);
      this.uppy.on('file-added', this.hideAllPanels);
      this.uppy.on('dashboard:modal-closed', this.hideAllPanels);
      this.uppy.on('file-editor:complete', this.hideAllPanels);
      this.uppy.on('complete', this.handleComplete);
      this.uppy.on('files-added', _classPrivateFieldLooseBase(this, _generateLargeThumbnailIfSingleFile)[_generateLargeThumbnailIfSingleFile]);
      this.uppy.on('file-removed', _classPrivateFieldLooseBase(this, _generateLargeThumbnailIfSingleFile)[_generateLargeThumbnailIfSingleFile]); // ___Why fire on capture?
      //    Because this.ifFocusedOnUppyRecently needs to change before onUpdate() fires.

      document.addEventListener('focus', this.recordIfFocusedOnUppyRecently, true);
      document.addEventListener('click', this.recordIfFocusedOnUppyRecently, true);

      if (this.opts.inline) {
        this.el.addEventListener('keydown', this.handleKeyDownInInline);
      }

      if (this.opts.autoOpenFileEditor) {
        this.uppy.on('files-added', _classPrivateFieldLooseBase(this, _openFileEditorWhenFilesAdded)[_openFileEditorWhenFilesAdded]);
      }
    };

    this.removeEvents = () => {
      const showModalTrigger = (0,_uppy_utils_lib_findAllDOMElements__WEBPACK_IMPORTED_MODULE_5__["default"])(this.opts.trigger);

      if (!this.opts.inline && showModalTrigger) {
        showModalTrigger.forEach(trigger => trigger.removeEventListener('click', this.openModal));
      }

      this.stopListeningToResize();
      document.removeEventListener('paste', this.handlePasteOnBody);
      window.removeEventListener('popstate', this.handlePopState, false);
      this.uppy.off('plugin-remove', this.removeTarget);
      this.uppy.off('file-added', this.hideAllPanels);
      this.uppy.off('dashboard:modal-closed', this.hideAllPanels);
      this.uppy.off('file-editor:complete', this.hideAllPanels);
      this.uppy.off('complete', this.handleComplete);
      this.uppy.off('files-added', _classPrivateFieldLooseBase(this, _generateLargeThumbnailIfSingleFile)[_generateLargeThumbnailIfSingleFile]);
      this.uppy.off('file-removed', _classPrivateFieldLooseBase(this, _generateLargeThumbnailIfSingleFile)[_generateLargeThumbnailIfSingleFile]);
      document.removeEventListener('focus', this.recordIfFocusedOnUppyRecently);
      document.removeEventListener('click', this.recordIfFocusedOnUppyRecently);

      if (this.opts.inline) {
        this.el.removeEventListener('keydown', this.handleKeyDownInInline);
      }

      if (this.opts.autoOpenFileEditor) {
        this.uppy.off('files-added', _classPrivateFieldLooseBase(this, _openFileEditorWhenFilesAdded)[_openFileEditorWhenFilesAdded]);
      }
    };

    this.superFocusOnEachUpdate = () => {
      const isFocusInUppy = this.el.contains(document.activeElement); // When focus is lost on the page (== focus is on body for most browsers, or focus is null for IE11)

      const isFocusNowhere = document.activeElement === document.body || document.activeElement === null;
      const isInformerHidden = this.uppy.getState().info.length === 0;
      const isModal = !this.opts.inline;

      if ( // If update is connected to showing the Informer - let the screen reader calmly read it.
      isInformerHidden && ( // If we are in a modal - always superfocus without concern for other elements
      // on the page (user is unlikely to want to interact with the rest of the page)
      isModal // If we are already inside of Uppy, or
      || isFocusInUppy // If we are not focused on anything BUT we have already, at least once, focused on uppy
      //   1. We focus when isFocusNowhere, because when the element we were focused
      //      on disappears (e.g. an overlay), - focus gets lost. If user is typing
      //      something somewhere else on the page, - focus won't be 'nowhere'.
      //   2. We only focus when focus is nowhere AND this.ifFocusedOnUppyRecently,
      //      to avoid focus jumps if we do something else on the page.
      //   [Practical check] Without '&& this.ifFocusedOnUppyRecently', in Safari, in inline mode,
      //                     when file is uploading, - navigate via tab to the checkbox,
      //                     try to press space multiple times. Focus will jump to Uppy.
      || isFocusNowhere && this.ifFocusedOnUppyRecently)) {
        this.superFocus(this.el, this.getPluginState().activeOverlayType);
      } else {
        this.superFocus.cancel();
      }
    };

    this.afterUpdate = () => {
      if (this.opts.disabled && !this.dashboardIsDisabled) {
        this.disableInteractiveElements(true);
        return;
      }

      if (!this.opts.disabled && this.dashboardIsDisabled) {
        this.disableInteractiveElements(false);
      }

      this.superFocusOnEachUpdate();
    };

    this.saveFileCard = (meta, fileID) => {
      this.uppy.setFileMeta(fileID, meta);
      this.toggleFileCard(false, fileID);
    };

    Object.defineProperty(this, _attachRenderFunctionToTarget, {
      writable: true,
      value: target => {
        const plugin = this.uppy.getPlugin(target.id);
        return { ...target,
          icon: plugin.icon || this.opts.defaultPickerIcon,
          render: plugin.render
        };
      }
    });
    Object.defineProperty(this, _isTargetSupported, {
      writable: true,
      value: target => {
        const plugin = this.uppy.getPlugin(target.id); // If the plugin does not provide a `supported` check, assume the plugin works everywhere.

        if (typeof plugin.isSupported !== 'function') {
          return true;
        }

        return plugin.isSupported();
      }
    });
    Object.defineProperty(this, _getAcquirers, {
      writable: true,
      value: memoize(targets => {
        return targets.filter(target => target.type === 'acquirer' && _classPrivateFieldLooseBase(this, _isTargetSupported)[_isTargetSupported](target)).map(_classPrivateFieldLooseBase(this, _attachRenderFunctionToTarget)[_attachRenderFunctionToTarget]);
      })
    });
    Object.defineProperty(this, _getProgressIndicators, {
      writable: true,
      value: memoize(targets => {
        return targets.filter(target => target.type === 'progressindicator').map(_classPrivateFieldLooseBase(this, _attachRenderFunctionToTarget)[_attachRenderFunctionToTarget]);
      })
    });
    Object.defineProperty(this, _getEditors, {
      writable: true,
      value: memoize(targets => {
        return targets.filter(target => target.type === 'editor').map(_classPrivateFieldLooseBase(this, _attachRenderFunctionToTarget)[_attachRenderFunctionToTarget]);
      })
    });

    this.render = state => {
      const pluginState = this.getPluginState();
      const {
        files,
        capabilities,
        allowNewUpload
      } = state;
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
        isAllPaused
      } = this.uppy.getObjectOfFilesPerState();

      const acquirers = _classPrivateFieldLooseBase(this, _getAcquirers)[_getAcquirers](pluginState.targets);

      const progressindicators = _classPrivateFieldLooseBase(this, _getProgressIndicators)[_getProgressIndicators](pluginState.targets);

      const editors = _classPrivateFieldLooseBase(this, _getEditors)[_getEditors](pluginState.targets);

      let theme;

      if (this.opts.theme === 'auto') {
        theme = capabilities.darkMode ? 'dark' : 'light';
      } else {
        theme = this.opts.theme;
      }

      if (['files', 'folders', 'both'].indexOf(this.opts.fileManagerSelectionType) < 0) {
        this.opts.fileManagerSelectionType = 'files'; // eslint-disable-next-line no-console

        console.warn(`Unsupported option for "fileManagerSelectionType". Using default of "${this.opts.fileManagerSelectionType}".`);
      }

      return (0,_components_Dashboard_js__WEBPACK_IMPORTED_MODULE_10__["default"])({
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
        handleDrop: this.handleDrop
      });
    };

    this.discoverProviderPlugins = () => {
      this.uppy.iteratePlugins(plugin => {
        if (plugin && !plugin.target && plugin.opts && plugin.opts.target === this.constructor) {
          this.addTarget(plugin);
        }
      });
    };

    this.install = () => {
      // Set default state for Dashboard
      this.setPluginState({
        isHidden: true,
        fileCardFor: null,
        activeOverlayType: null,
        showAddFilesPanel: false,
        activePickerPanel: false,
        showFileEditor: false,
        metaFields: this.opts.metaFields,
        targets: [],
        // We'll make them visible once .containerWidth is determined
        areInsidesReadyToBeVisible: false,
        isDraggingOver: false
      });
      const {
        inline,
        closeAfterFinish
      } = this.opts;

      if (inline && closeAfterFinish) {
        throw new Error('[Dashboard] `closeAfterFinish: true` cannot be used on an inline Dashboard, because an inline Dashboard cannot be closed at all. Either set `inline: false`, or disable the `closeAfterFinish` option.');
      }

      const {
        allowMultipleUploads,
        allowMultipleUploadBatches
      } = this.uppy.opts;

      if ((allowMultipleUploads || allowMultipleUploadBatches) && closeAfterFinish) {
        this.uppy.log('[Dashboard] When using `closeAfterFinish`, we recommended setting the `allowMultipleUploadBatches` option to `false` in the Uppy constructor. See https://uppy.io/docs/uppy/#allowMultipleUploads-true', 'warning');
      }

      const {
        target
      } = this.opts;

      if (target) {
        this.mount(target, this);
      }

      const plugins = this.opts.plugins || [];
      plugins.forEach(pluginID => {
        const plugin = this.uppy.getPlugin(pluginID);

        if (plugin) {
          plugin.mount(this, plugin);
        }
      });

      if (!this.opts.disableStatusBar) {
        this.uppy.use(_uppy_status_bar__WEBPACK_IMPORTED_MODULE_2__["default"], {
          id: `${this.id}:StatusBar`,
          target: this,
          hideUploadButton: this.opts.hideUploadButton,
          hideRetryButton: this.opts.hideRetryButton,
          hidePauseResumeButton: this.opts.hidePauseResumeButton,
          hideCancelButton: this.opts.hideCancelButton,
          showProgressDetails: this.opts.showProgressDetails,
          hideAfterFinish: this.opts.hideProgressAfterFinish,
          locale: this.opts.locale,
          doneButtonHandler: this.opts.doneButtonHandler
        });
      }

      if (!this.opts.disableInformer) {
        this.uppy.use(_uppy_informer__WEBPACK_IMPORTED_MODULE_3__["default"], {
          id: `${this.id}:Informer`,
          target: this
        });
      }

      if (!this.opts.disableThumbnailGenerator) {
        this.uppy.use(_uppy_thumbnail_generator__WEBPACK_IMPORTED_MODULE_4__["default"], {
          id: `${this.id}:ThumbnailGenerator`,
          thumbnailWidth: this.opts.thumbnailWidth,
          thumbnailHeight: this.opts.thumbnailHeight,
          thumbnailType: this.opts.thumbnailType,
          waitForThumbnailsBeforeUpload: this.opts.waitForThumbnailsBeforeUpload,
          // If we don't block on thumbnails, we can lazily generate them
          lazy: !this.opts.waitForThumbnailsBeforeUpload
        });
      } // Dark Mode / theme


      this.darkModeMediaQuery = typeof window !== 'undefined' && window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
      const isDarkModeOnFromTheStart = this.darkModeMediaQuery ? this.darkModeMediaQuery.matches : false;
      this.uppy.log(`[Dashboard] Dark mode is ${isDarkModeOnFromTheStart ? 'on' : 'off'}`);
      this.setDarkModeCapability(isDarkModeOnFromTheStart);

      if (this.opts.theme === 'auto') {
        this.darkModeMediaQuery.addListener(this.handleSystemDarkModeChange);
      }

      this.discoverProviderPlugins();
      this.initEvents();
    };

    this.uninstall = () => {
      if (!this.opts.disableInformer) {
        const informer = this.uppy.getPlugin(`${this.id}:Informer`); // Checking if this plugin exists, in case it was removed by uppy-core
        // before the Dashboard was.

        if (informer) this.uppy.removePlugin(informer);
      }

      if (!this.opts.disableStatusBar) {
        const statusBar = this.uppy.getPlugin(`${this.id}:StatusBar`);
        if (statusBar) this.uppy.removePlugin(statusBar);
      }

      if (!this.opts.disableThumbnailGenerator) {
        const thumbnail = this.uppy.getPlugin(`${this.id}:ThumbnailGenerator`);
        if (thumbnail) this.uppy.removePlugin(thumbnail);
      }

      const plugins = this.opts.plugins || [];
      plugins.forEach(pluginID => {
        const plugin = this.uppy.getPlugin(pluginID);
        if (plugin) plugin.unmount();
      });

      if (this.opts.theme === 'auto') {
        this.darkModeMediaQuery.removeListener(this.handleSystemDarkModeChange);
      }

      this.unmount();
      this.removeEvents();
    };

    this.id = this.opts.id || 'Dashboard';
    this.title = 'Dashboard';
    this.type = 'orchestrator';
    this.modalName = `uppy-Dashboard-${(0,nanoid_non_secure__WEBPACK_IMPORTED_MODULE_13__.nanoid)()}`;
    this.defaultLocale = _locale_js__WEBPACK_IMPORTED_MODULE_11__["default"]; // set default options, must be kept in sync with packages/@uppy/react/src/DashboardModal.js

    const defaultOptions = {
      target: 'body',
      metaFields: [],
      trigger: null,
      inline: false,
      width: 750,
      height: 550,
      thumbnailWidth: 280,
      thumbnailType: 'image/jpeg',
      waitForThumbnailsBeforeUpload: false,
      defaultPickerIcon,
      showLinkToFileUploadResult: false,
      showProgressDetails: false,
      hideUploadButton: false,
      hideCancelButton: false,
      hideRetryButton: false,
      hidePauseResumeButton: false,
      hideProgressAfterFinish: false,
      doneButtonHandler: () => {
        this.uppy.cancelAll();
        this.requestCloseModal();
      },
      note: null,
      closeModalOnClickOutside: false,
      closeAfterFinish: false,
      singleFileFullScreen: true,
      disableStatusBar: false,
      disableInformer: false,
      disableThumbnailGenerator: false,
      disablePageScrollWhenModalOpen: true,
      animateOpenClose: true,
      fileManagerSelectionType: 'files',
      proudlyDisplayPoweredByUppy: true,
      onRequestCloseModal: () => this.closeModal(),
      showSelectedFiles: true,
      showRemoveButtonAfterComplete: false,
      browserBackButtonClose: false,
      showNativePhotoCameraButton: false,
      showNativeVideoCameraButton: false,
      theme: 'light',
      autoOpenFileEditor: false,
      disabled: false,
      disableLocalFiles: false
    }; // merge default options with the ones set by user

    this.opts = { ...defaultOptions,
      ..._opts
    };
    this.i18nInit();
    this.superFocus = (0,_utils_createSuperFocus_js__WEBPACK_IMPORTED_MODULE_9__["default"])();
    this.ifFocusedOnUppyRecently = false; // Timeouts

    this.makeDashboardInsidesVisibleAnywayTimeout = null;
    this.removeDragOverClassTimeout = null;
  }

}
Dashboard.VERSION = packageJson.version;

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/AddFiles.js":
/*!*********************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/AddFiles.js ***!
  \*********************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
let _Symbol$for;


_Symbol$for = Symbol.for('uppy test: disable unused locale key warning');

class AddFiles extends preact__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);

    this.triggerFileInputClick = () => {
      this.fileInput.click();
    };

    this.triggerFolderInputClick = () => {
      this.folderInput.click();
    };

    this.triggerVideoCameraInputClick = () => {
      this.mobileVideoFileInput.click();
    };

    this.triggerPhotoCameraInputClick = () => {
      this.mobilePhotoFileInput.click();
    };

    this.onFileInputChange = event => {
      this.props.handleInputChange(event); // We clear the input after a file is selected, because otherwise
      // change event is not fired in Chrome and Safari when a file
      // with the same name is selected.
      // ___Why not use value="" on <input/> instead?
      //    Because if we use that method of clearing the input,
      //    Chrome will not trigger change if we drop the same file twice (Issue #768).

      event.target.value = null; // eslint-disable-line no-param-reassign
    };

    this.renderHiddenInput = (isFolder, refCallback) => {
      return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("input", {
        className: "uppy-Dashboard-input",
        hidden: true,
        "aria-hidden": "true",
        tabIndex: -1,
        webkitdirectory: isFolder,
        type: "file",
        name: "files[]",
        multiple: this.props.maxNumberOfFiles !== 1,
        onChange: this.onFileInputChange,
        accept: this.props.allowedFileTypes,
        ref: refCallback
      });
    };

    this.renderHiddenCameraInput = (type, nativeCameraFacingMode, refCallback) => {
      const typeToAccept = {
        photo: 'image/*',
        video: 'video/*'
      };
      const accept = typeToAccept[type];
      return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("input", {
        className: "uppy-Dashboard-input",
        hidden: true,
        "aria-hidden": "true",
        tabIndex: -1,
        type: "file",
        name: `camera-${type}`,
        onChange: this.onFileInputChange,
        capture: nativeCameraFacingMode,
        accept: accept,
        ref: refCallback
      });
    };

    this.renderMyDeviceAcquirer = () => {
      return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
        className: "uppy-DashboardTab",
        role: "presentation",
        "data-uppy-acquirer-id": "MyDevice"
      }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
        type: "button",
        className: "uppy-u-reset uppy-c-btn uppy-DashboardTab-btn",
        role: "tab",
        tabIndex: 0,
        "data-uppy-super-focusable": true,
        onClick: this.triggerFileInputClick
      }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
        className: "uppy-DashboardTab-inner"
      }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
        className: "uppy-DashboardTab-iconMyDevice",
        "aria-hidden": "true",
        focusable: "false",
        width: "32",
        height: "32",
        viewBox: "0 0 32 32"
      }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
        d: "M8.45 22.087l-1.305-6.674h17.678l-1.572 6.674H8.45zm4.975-12.412l1.083 1.765a.823.823 0 00.715.386h7.951V13.5H8.587V9.675h4.838zM26.043 13.5h-1.195v-2.598c0-.463-.336-.75-.798-.75h-8.356l-1.082-1.766A.823.823 0 0013.897 8H7.728c-.462 0-.815.256-.815.718V13.5h-.956a.97.97 0 00-.746.37.972.972 0 00-.19.81l1.724 8.565c.095.44.484.755.933.755H24c.44 0 .824-.3.929-.727l2.043-8.568a.972.972 0 00-.176-.825.967.967 0 00-.753-.38z",
        fill: "currentcolor",
        "fill-rule": "evenodd"
      }))), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
        className: "uppy-DashboardTab-name"
      }, this.props.i18n('myDevice'))));
    };

    this.renderPhotoCamera = () => {
      return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
        className: "uppy-DashboardTab",
        role: "presentation",
        "data-uppy-acquirer-id": "MobilePhotoCamera"
      }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
        type: "button",
        className: "uppy-u-reset uppy-c-btn uppy-DashboardTab-btn",
        role: "tab",
        tabIndex: 0,
        "data-uppy-super-focusable": true,
        onClick: this.triggerPhotoCameraInputClick
      }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
        className: "uppy-DashboardTab-inner"
      }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        width: "32",
        height: "32",
        viewBox: "0 0 32 32"
      }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
        d: "M23.5 9.5c1.417 0 2.5 1.083 2.5 2.5v9.167c0 1.416-1.083 2.5-2.5 2.5h-15c-1.417 0-2.5-1.084-2.5-2.5V12c0-1.417 1.083-2.5 2.5-2.5h2.917l1.416-2.167C13 7.167 13.25 7 13.5 7h5c.25 0 .5.167.667.333L20.583 9.5H23.5zM16 11.417a4.706 4.706 0 00-4.75 4.75 4.704 4.704 0 004.75 4.75 4.703 4.703 0 004.75-4.75c0-2.663-2.09-4.75-4.75-4.75zm0 7.825c-1.744 0-3.076-1.332-3.076-3.074 0-1.745 1.333-3.077 3.076-3.077 1.744 0 3.074 1.333 3.074 3.076s-1.33 3.075-3.074 3.075z",
        fill: "#02B383",
        "fill-rule": "nonzero"
      }))), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
        className: "uppy-DashboardTab-name"
      }, this.props.i18n('takePictureBtn'))));
    };

    this.renderVideoCamera = () => {
      return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
        className: "uppy-DashboardTab",
        role: "presentation",
        "data-uppy-acquirer-id": "MobileVideoCamera"
      }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
        type: "button",
        className: "uppy-u-reset uppy-c-btn uppy-DashboardTab-btn",
        role: "tab",
        tabIndex: 0,
        "data-uppy-super-focusable": true,
        onClick: this.triggerVideoCameraInputClick
      }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
        className: "uppy-DashboardTab-inner"
      }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
        "aria-hidden": "true",
        width: "32",
        height: "32",
        viewBox: "0 0 32 32"
      }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
        fill: "#FF675E",
        fillRule: "nonzero",
        d: "m21.254 14.277 2.941-2.588c.797-.313 1.243.818 1.09 1.554-.01 2.094.02 4.189-.017 6.282-.126.915-1.145 1.08-1.58.34l-2.434-2.142c-.192.287-.504 1.305-.738.468-.104-1.293-.028-2.596-.05-3.894.047-.312.381.823.426 1.069.063-.384.206-.744.362-1.09zm-12.939-3.73c3.858.013 7.717-.025 11.574.02.912.129 1.492 1.237 1.351 2.217-.019 2.412.04 4.83-.03 7.239-.17 1.025-1.166 1.59-2.029 1.429-3.705-.012-7.41.025-11.114-.019-.913-.129-1.492-1.237-1.352-2.217.018-2.404-.036-4.813.029-7.214.136-.82.83-1.473 1.571-1.454z "
      }))), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
        className: "uppy-DashboardTab-name"
      }, this.props.i18n('recordVideoBtn'))));
    };

    this.renderBrowseButton = (text, onClickFn) => {
      const numberOfAcquirers = this.props.acquirers.length;
      return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
        type: "button",
        className: "uppy-u-reset uppy-c-btn uppy-Dashboard-browse",
        onClick: onClickFn,
        "data-uppy-super-focusable": numberOfAcquirers === 0
      }, text);
    };

    this.renderDropPasteBrowseTagline = numberOfAcquirers => {
      const browseFiles = this.renderBrowseButton(this.props.i18n('browseFiles'), this.triggerFileInputClick);
      const browseFolders = this.renderBrowseButton(this.props.i18n('browseFolders'), this.triggerFolderInputClick); // in order to keep the i18n CamelCase and options lower (as are defaults) we will want to transform a lower
      // to Camel

      const lowerFMSelectionType = this.props.fileManagerSelectionType;
      const camelFMSelectionType = lowerFMSelectionType.charAt(0).toUpperCase() + lowerFMSelectionType.slice(1);
      return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
        class: "uppy-Dashboard-AddFiles-title"
      }, // eslint-disable-next-line no-nested-ternary
      this.props.disableLocalFiles ? this.props.i18n('importFiles') : numberOfAcquirers > 0 ? this.props.i18nArray(`dropPasteImport${camelFMSelectionType}`, {
        browseFiles,
        browseFolders,
        browse: browseFiles
      }) : this.props.i18nArray(`dropPaste${camelFMSelectionType}`, {
        browseFiles,
        browseFolders,
        browse: browseFiles
      }));
    };

    this.renderAcquirer = acquirer => {
      return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
        className: "uppy-DashboardTab",
        role: "presentation",
        "data-uppy-acquirer-id": acquirer.id
      }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
        type: "button",
        className: "uppy-u-reset uppy-c-btn uppy-DashboardTab-btn",
        role: "tab",
        tabIndex: 0,
        "data-cy": acquirer.id,
        "aria-controls": `uppy-DashboardContent-panel--${acquirer.id}`,
        "aria-selected": this.props.activePickerPanel.id === acquirer.id,
        "data-uppy-super-focusable": true,
        onClick: () => this.props.showPanel(acquirer.id)
      }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
        className: "uppy-DashboardTab-inner"
      }, acquirer.icon()), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
        className: "uppy-DashboardTab-name"
      }, acquirer.name)));
    };

    this.renderAcquirers = acquirers => {
      // Group last two buttons, so we don’t end up with
      // just one button on a new line
      const acquirersWithoutLastTwo = [...acquirers];
      const lastTwoAcquirers = acquirersWithoutLastTwo.splice(acquirers.length - 2, acquirers.length);
      return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(preact__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, acquirersWithoutLastTwo.map(acquirer => this.renderAcquirer(acquirer)), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("span", {
        role: "presentation",
        style: {
          'white-space': 'nowrap'
        }
      }, lastTwoAcquirers.map(acquirer => this.renderAcquirer(acquirer))));
    };

    this.renderSourcesList = (acquirers, disableLocalFiles) => {
      const {
        showNativePhotoCameraButton,
        showNativeVideoCameraButton
      } = this.props;
      let list = [];
      const myDeviceKey = 'myDevice';

      if (!disableLocalFiles) {
        list.push({
          key: myDeviceKey,
          elements: this.renderMyDeviceAcquirer()
        });
        if (showNativePhotoCameraButton) list.push({
          key: 'nativePhotoCameraButton',
          elements: this.renderPhotoCamera()
        });
        if (showNativeVideoCameraButton) list.push({
          key: 'nativePhotoCameraButton',
          elements: this.renderVideoCamera()
        });
      }

      list.push(...acquirers.map(acquirer => ({
        key: acquirer.id,
        elements: this.renderAcquirer(acquirer)
      }))); // doesn't make sense to show only a lonely "My Device"

      const hasOnlyMyDevice = list.length === 1 && list[0].key === myDeviceKey;
      if (hasOnlyMyDevice) list = []; // Group last two buttons, so we don’t end up with
      // just one button on a new line

      const listWithoutLastTwo = [...list];
      const lastTwo = listWithoutLastTwo.splice(list.length - 2, list.length);

      const renderList = l => l.map(_ref => {
        let {
          key,
          elements
        } = _ref;
        return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(preact__WEBPACK_IMPORTED_MODULE_0__.Fragment, {
          key: key
        }, elements);
      });

      return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(preact__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, this.renderDropPasteBrowseTagline(list.length), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
        className: "uppy-Dashboard-AddFiles-list",
        role: "tablist"
      }, renderList(listWithoutLastTwo), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("span", {
        role: "presentation",
        style: {
          'white-space': 'nowrap'
        }
      }, renderList(lastTwo))));
    };
  }

  [_Symbol$for]() {
    // Those are actually used in `renderDropPasteBrowseTagline` method.
    this.props.i18nArray('dropPasteBoth');
    this.props.i18nArray('dropPasteFiles');
    this.props.i18nArray('dropPasteFolders');
    this.props.i18nArray('dropPasteImportBoth');
    this.props.i18nArray('dropPasteImportFiles');
    this.props.i18nArray('dropPasteImportFolders');
  }

  renderPoweredByUppy() {
    const {
      i18nArray
    } = this.props;
    const uppyBranding = (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("span", null, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      className: "uppy-c-icon uppy-Dashboard-poweredByIcon",
      width: "11",
      height: "11",
      viewBox: "0 0 11 11"
    }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
      d: "M7.365 10.5l-.01-4.045h2.612L5.5.806l-4.467 5.65h2.604l.01 4.044h3.718z",
      fillRule: "evenodd"
    })), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("span", {
      className: "uppy-Dashboard-poweredByUppy"
    }, "Uppy"));
    const linkText = i18nArray('poweredBy', {
      uppy: uppyBranding
    });
    return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("a", {
      tabIndex: "-1",
      href: "https://uppy.io",
      rel: "noreferrer noopener",
      target: "_blank",
      className: "uppy-Dashboard-poweredBy"
    }, linkText);
  }

  render() {
    const {
      showNativePhotoCameraButton,
      showNativeVideoCameraButton,
      nativeCameraFacingMode
    } = this.props;
    return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
      className: "uppy-Dashboard-AddFiles"
    }, this.renderHiddenInput(false, ref => {
      this.fileInput = ref;
    }), this.renderHiddenInput(true, ref => {
      this.folderInput = ref;
    }), showNativePhotoCameraButton && this.renderHiddenCameraInput('photo', nativeCameraFacingMode, ref => {
      this.mobilePhotoFileInput = ref;
    }), showNativeVideoCameraButton && this.renderHiddenCameraInput('video', nativeCameraFacingMode, ref => {
      this.mobileVideoFileInput = ref;
    }), this.renderSourcesList(this.props.acquirers, this.props.disableLocalFiles), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
      className: "uppy-Dashboard-AddFiles-info"
    }, this.props.note && (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
      className: "uppy-Dashboard-note"
    }, this.props.note), this.props.proudlyDisplayPoweredByUppy && this.renderPoweredByUppy(this.props)));
  }

}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (AddFiles);

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/AddFilesPanel.js":
/*!**************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/AddFilesPanel.js ***!
  \**************************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! classnames */ "./src/jgDashboard/node_modules/classnames/index.js");
/* harmony import */ var _AddFiles_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AddFiles.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/AddFiles.js");




const AddFilesPanel = props => {
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: classnames__WEBPACK_IMPORTED_MODULE_1__('uppy-Dashboard-AddFilesPanel', props.className),
    "data-uppy-panelType": "AddFiles",
    "aria-hidden": props.showAddFilesPanel
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-DashboardContent-bar"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-DashboardContent-title",
    role: "heading",
    "aria-level": "1"
  }, props.i18n('addingMoreFiles')), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    className: "uppy-DashboardContent-back",
    type: "button",
    onClick: () => props.toggleAddFilesPanel(false)
  }, props.i18n('back'))), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_AddFiles_js__WEBPACK_IMPORTED_MODULE_2__["default"], props));
};

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (AddFilesPanel);

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/Dashboard.js":
/*!**********************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/Dashboard.js ***!
  \**********************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Dashboard)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! classnames */ "./src/jgDashboard/node_modules/classnames/index.js");
/* harmony import */ var _uppy_utils_lib_isDragDropSupported__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @uppy/utils/lib/isDragDropSupported */ "./src/jgDashboard/node_modules/@uppy/utils/lib/isDragDropSupported.js");
/* harmony import */ var _FileList_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./FileList.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileList.js");
/* harmony import */ var _AddFiles_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./AddFiles.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/AddFiles.js");
/* harmony import */ var _AddFilesPanel_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./AddFilesPanel.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/AddFilesPanel.js");
/* harmony import */ var _PickerPanelContent_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./PickerPanelContent.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/PickerPanelContent.js");
/* harmony import */ var _EditorPanel_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./EditorPanel.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/EditorPanel.js");
/* harmony import */ var _PickerPanelTopBar_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./PickerPanelTopBar.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/PickerPanelTopBar.js");
/* harmony import */ var _FileCard_index_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./FileCard/index.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileCard/index.js");
/* harmony import */ var _Slide_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./Slide.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/Slide.js");
function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }











 // http://dev.edenspiekermann.com/2016/02/11/introducing-accessible-modal-dialog
// https://github.com/ghosh/micromodal

const WIDTH_XL = 900;
const WIDTH_LG = 700;
const WIDTH_MD = 576;
const HEIGHT_MD = 330; // We might want to enable this in the future
// const HEIGHT_LG = 400
// const HEIGHT_XL = 460

function Dashboard(props) {
  const isNoFiles = props.totalFileCount === 0;
  const isSingleFile = props.totalFileCount === 1;
  const isSizeMD = props.containerWidth > WIDTH_MD;
  const isSizeHeightMD = props.containerHeight > HEIGHT_MD;
  const dashboardClassName = classnames__WEBPACK_IMPORTED_MODULE_1__({
    'uppy-Dashboard': true,
    'uppy-Dashboard--isDisabled': props.disabled,
    'uppy-Dashboard--animateOpenClose': props.animateOpenClose,
    'uppy-Dashboard--isClosing': props.isClosing,
    'uppy-Dashboard--isDraggingOver': props.isDraggingOver,
    'uppy-Dashboard--modal': !props.inline,
    'uppy-size--md': props.containerWidth > WIDTH_MD,
    'uppy-size--lg': props.containerWidth > WIDTH_LG,
    'uppy-size--xl': props.containerWidth > WIDTH_XL,
    'uppy-size--height-md': props.containerHeight > HEIGHT_MD,
    // We might want to enable this in the future
    // 'uppy-size--height-lg': props.containerHeight > HEIGHT_LG,
    // 'uppy-size--height-xl': props.containerHeight > HEIGHT_XL,
    'uppy-Dashboard--isAddFilesPanelVisible': props.showAddFilesPanel,
    'uppy-Dashboard--isInnerWrapVisible': props.areInsidesReadyToBeVisible,
    // Only enable “centered single file” mode when Dashboard is tall enough
    'uppy-Dashboard--singleFile': props.singleFileFullScreen && isSingleFile && isSizeHeightMD
  }); // Important: keep these in sync with the percent width values in `src/components/FileItem/index.scss`.

  let itemsPerRow = 1; // mobile

  if (props.containerWidth > WIDTH_XL) {
    itemsPerRow = 5;
  } else if (props.containerWidth > WIDTH_LG) {
    itemsPerRow = 4;
  } else if (props.containerWidth > WIDTH_MD) {
    itemsPerRow = 3;
  }

  const showFileList = props.showSelectedFiles && !isNoFiles;
  const numberOfFilesForRecovery = props.recoveredState ? Object.keys(props.recoveredState.files).length : null;
  const numberOfGhosts = props.files ? Object.keys(props.files).filter(fileID => props.files[fileID].isGhost).length : null;

  const renderRestoredText = () => {
    if (numberOfGhosts > 0) {
      return props.i18n('recoveredXFiles', {
        smart_count: numberOfGhosts
      });
    }

    return props.i18n('recoveredAllFiles');
  };

  const dashboard = (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: dashboardClassName,
    "data-uppy-theme": props.theme,
    "data-uppy-num-acquirers": props.acquirers.length,
    "data-uppy-drag-drop-supported": !props.disableLocalFiles && (0,_uppy_utils_lib_isDragDropSupported__WEBPACK_IMPORTED_MODULE_2__["default"])(),
    "aria-hidden": props.inline ? 'false' : props.isHidden,
    "aria-disabled": props.disabled,
    "aria-label": !props.inline ? props.i18n('dashboardWindowTitle') : props.i18n('dashboardTitle'),
    onPaste: props.handlePaste,
    onDragOver: props.handleDragOver,
    onDragLeave: props.handleDragLeave,
    onDrop: props.handleDrop
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    "aria-hidden": "true",
    className: "uppy-Dashboard-overlay",
    tabIndex: -1,
    onClick: props.handleClickOutside
  }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-inner",
    "aria-modal": !props.inline && 'true',
    role: !props.inline && 'dialog',
    style: {
      width: props.inline && props.width ? props.width : '',
      height: props.inline && props.height ? props.height : ''
    }
  }, !props.inline ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    className: "uppy-u-reset uppy-Dashboard-close",
    type: "button",
    "aria-label": props.i18n('closeModal'),
    title: props.i18n('closeModal'),
    onClick: props.closeModal
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("span", {
    "aria-hidden": "true"
  }, "\xD7")) : null, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-innerWrap"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-dropFilesHereHint"
  }, props.i18n('dropHint')), showFileList && (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_PickerPanelTopBar_js__WEBPACK_IMPORTED_MODULE_8__["default"], props), numberOfFilesForRecovery && (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-serviceMsg"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    className: "uppy-Dashboard-serviceMsg-icon",
    "aria-hidden": "true",
    focusable: "false",
    width: "21",
    height: "16",
    viewBox: "0 0 24 19"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("g", {
    transform: "translate(0 -1)",
    fill: "none",
    fillRule: "evenodd"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    d: "M12.857 1.43l10.234 17.056A1 1 0 0122.234 20H1.766a1 1 0 01-.857-1.514L11.143 1.429a1 1 0 011.714 0z",
    fill: "#FFD300"
  }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    fill: "#000",
    d: "M11 6h2l-.3 8h-1.4z"
  }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("circle", {
    fill: "#000",
    cx: "12",
    cy: "17",
    r: "1"
  }))), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("strong", {
    className: "uppy-Dashboard-serviceMsg-title"
  }, props.i18n('sessionRestored')), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-serviceMsg-text"
  }, renderRestoredText())), showFileList ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_FileList_js__WEBPACK_IMPORTED_MODULE_3__["default"], {
    id: props.id,
    error: props.error,
    i18n: props.i18n,
    uppy: props.uppy,
    files: props.files,
    acquirers: props.acquirers,
    resumableUploads: props.resumableUploads,
    hideRetryButton: props.hideRetryButton,
    hidePauseResumeButton: props.hidePauseResumeButton,
    hideCancelButton: props.hideCancelButton,
    showLinkToFileUploadResult: props.showLinkToFileUploadResult,
    showRemoveButtonAfterComplete: props.showRemoveButtonAfterComplete,
    isWide: props.isWide,
    metaFields: props.metaFields,
    toggleFileCard: props.toggleFileCard,
    handleRequestThumbnail: props.handleRequestThumbnail,
    handleCancelThumbnail: props.handleCancelThumbnail,
    recoveredState: props.recoveredState,
    individualCancellation: props.individualCancellation,
    openFileEditor: props.openFileEditor,
    canEditFile: props.canEditFile,
    toggleAddFilesPanel: props.toggleAddFilesPanel,
    isSingleFile: isSingleFile,
    itemsPerRow: itemsPerRow
  }) : // eslint-disable-next-line react/jsx-props-no-spreading
  (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_AddFiles_js__WEBPACK_IMPORTED_MODULE_4__["default"], _extends({}, props, {
    isSizeMD: isSizeMD
  })), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_Slide_js__WEBPACK_IMPORTED_MODULE_10__["default"], null, props.showAddFilesPanel ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_AddFilesPanel_js__WEBPACK_IMPORTED_MODULE_5__["default"], _extends({
    key: "AddFiles"
  }, props, {
    isSizeMD: isSizeMD
  })) : null), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_Slide_js__WEBPACK_IMPORTED_MODULE_10__["default"], null, props.fileCardFor ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_FileCard_index_js__WEBPACK_IMPORTED_MODULE_9__["default"], _extends({
    key: "FileCard"
  }, props)) : null), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_Slide_js__WEBPACK_IMPORTED_MODULE_10__["default"], null, props.activePickerPanel ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_PickerPanelContent_js__WEBPACK_IMPORTED_MODULE_6__["default"], _extends({
    key: "Picker"
  }, props)) : null), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_Slide_js__WEBPACK_IMPORTED_MODULE_10__["default"], null, props.showFileEditor ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_EditorPanel_js__WEBPACK_IMPORTED_MODULE_7__["default"], _extends({
    key: "Editor"
  }, props)) : null), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-progressindicators"
  }, props.progressindicators.map(target => {
    return props.uppy.getPlugin(target.id).render(props.state);
  })))));
  return dashboard;
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/EditorPanel.js":
/*!************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/EditorPanel.js ***!
  \************************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! classnames */ "./src/jgDashboard/node_modules/classnames/index.js");



function EditorPanel(props) {
  const file = props.files[props.fileCardFor];
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: classnames__WEBPACK_IMPORTED_MODULE_1__('uppy-DashboardContent-panel', props.className),
    role: "tabpanel",
    "data-uppy-panelType": "FileEditor",
    id: "uppy-DashboardContent-panel--editor"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-DashboardContent-bar"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-DashboardContent-title",
    role: "heading",
    "aria-level": "1"
  }, props.i18nArray('editing', {
    file: (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("span", {
      className: "uppy-DashboardContent-titleFile"
    }, file.meta ? file.meta.name : file.name)
  })), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    className: "uppy-DashboardContent-back",
    type: "button",
    onClick: props.hideAllPanels
  }, props.i18n('cancel')), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    className: "uppy-DashboardContent-save",
    type: "button",
    onClick: props.saveFileEditor
  }, props.i18n('save'))), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-DashboardContent-panelBody"
  }, props.editors.map(target => {
    return props.uppy.getPlugin(target.id).render(props.state);
  })));
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (EditorPanel);

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileCard/RenderMetaFields.js":
/*!**************************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileCard/RenderMetaFields.js ***!
  \**************************************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ RenderMetaFields)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");

function RenderMetaFields(props) {
  const {
    computedMetaFields,
    requiredMetaFields,
    updateMeta,
    form,
    formState
  } = props;
  const fieldCSSClasses = {
    text: 'uppy-u-reset uppy-c-textInput uppy-Dashboard-FileCard-input'
  };
  return computedMetaFields.map(field => {
    const id = `uppy-Dashboard-FileCard-input-${field.id}`;
    const required = requiredMetaFields.includes(field.id);
    return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("fieldset", {
      key: field.id,
      className: "uppy-Dashboard-FileCard-fieldset"
    }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("label", {
      className: "uppy-Dashboard-FileCard-label",
      htmlFor: id
    }, field.name), field.render !== undefined ? field.render({
      value: formState[field.id],
      onChange: newVal => updateMeta(newVal, field.id),
      fieldCSSClasses,
      required,
      form: form.id
    }, preact__WEBPACK_IMPORTED_MODULE_0__.h) : (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("input", {
      className: fieldCSSClasses.text,
      id: id,
      form: form.id,
      type: field.type || 'text',
      required: required,
      value: formState[field.id],
      placeholder: field.placeholder,
      onInput: ev => updateMeta(ev.target.value, field.id),
      "data-uppy-super-focusable": true
    }));
  });
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileCard/index.js":
/*!***************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileCard/index.js ***!
  \***************************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ FileCard)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var preact_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! preact/hooks */ "./src/jgDashboard/node_modules/preact/hooks/dist/hooks.module.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! classnames */ "./src/jgDashboard/node_modules/classnames/index.js");
/* harmony import */ var nanoid_non_secure__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! nanoid/non-secure */ "./src/jgDashboard/node_modules/nanoid/non-secure/index.js");
/* harmony import */ var _utils_getFileTypeIcon_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../utils/getFileTypeIcon.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/getFileTypeIcon.js");
/* harmony import */ var _utils_ignoreEvent_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../utils/ignoreEvent.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/ignoreEvent.js");
/* harmony import */ var _FilePreview_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../FilePreview.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FilePreview.js");
/* harmony import */ var _RenderMetaFields_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./RenderMetaFields.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileCard/RenderMetaFields.js");








function FileCard(props) {
  var _getMetaFields;

  const {
    uppy,
    files,
    fileCardFor,
    toggleFileCard,
    saveFileCard,
    metaFields,
    requiredMetaFields,
    openFileEditor,
    i18n,
    i18nArray,
    className,
    canEditFile
  } = props;

  const getMetaFields = () => {
    return typeof metaFields === 'function' ? metaFields(files[fileCardFor]) : metaFields;
  };

  const file = files[fileCardFor];
  const computedMetaFields = (_getMetaFields = getMetaFields()) != null ? _getMetaFields : [];
  const showEditButton = canEditFile(file);
  const storedMetaData = {};
  computedMetaFields.forEach(field => {
    var _file$meta$field$id;

    storedMetaData[field.id] = (_file$meta$field$id = file.meta[field.id]) != null ? _file$meta$field$id : '';
  });
  const [formState, setFormState] = (0,preact_hooks__WEBPACK_IMPORTED_MODULE_1__.useState)(storedMetaData);
  const handleSave = (0,preact_hooks__WEBPACK_IMPORTED_MODULE_1__.useCallback)(ev => {
    ev.preventDefault();
    saveFileCard(formState, fileCardFor);
  }, [saveFileCard, formState, fileCardFor]);

  const updateMeta = (newVal, name) => {
    setFormState({
      [name]: newVal
    });
  };

  const handleCancel = () => {
    uppy.emit('file-editor:cancel', file);
    toggleFileCard(false);
  };

  const [form] = (0,preact_hooks__WEBPACK_IMPORTED_MODULE_1__.useState)(() => {
    const formEl = document.createElement('form');
    formEl.setAttribute('tabindex', '-1');
    formEl.id = (0,nanoid_non_secure__WEBPACK_IMPORTED_MODULE_7__.nanoid)();
    return formEl;
  });
  (0,preact_hooks__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    document.body.appendChild(form);
    form.addEventListener('submit', handleSave);
    return () => {
      form.removeEventListener('submit', handleSave);
      document.body.removeChild(form);
    };
  }, [form, handleSave]);
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: classnames__WEBPACK_IMPORTED_MODULE_2__('uppy-Dashboard-FileCard', className),
    "data-uppy-panelType": "FileCard",
    onDragOver: _utils_ignoreEvent_js__WEBPACK_IMPORTED_MODULE_4__["default"],
    onDragLeave: _utils_ignoreEvent_js__WEBPACK_IMPORTED_MODULE_4__["default"],
    onDrop: _utils_ignoreEvent_js__WEBPACK_IMPORTED_MODULE_4__["default"],
    onPaste: _utils_ignoreEvent_js__WEBPACK_IMPORTED_MODULE_4__["default"]
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-DashboardContent-bar"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-DashboardContent-title",
    role: "heading",
    "aria-level": "1"
  }, i18nArray('editing', {
    file: (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("span", {
      className: "uppy-DashboardContent-titleFile"
    }, file.meta ? file.meta.name : file.name)
  })), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    className: "uppy-DashboardContent-back",
    type: "button",
    form: form.id,
    title: i18n('finishEditingFile'),
    onClick: handleCancel
  }, i18n('cancel'))), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-FileCard-inner"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-FileCard-preview",
    style: {
      backgroundColor: (0,_utils_getFileTypeIcon_js__WEBPACK_IMPORTED_MODULE_3__["default"])(file.type).color
    }
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_FilePreview_js__WEBPACK_IMPORTED_MODULE_5__["default"], {
    file: file
  }), showEditButton && (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    type: "button",
    className: "uppy-u-reset uppy-c-btn uppy-Dashboard-FileCard-edit",
    onClick: event => {
      // When opening the image editor we want to save any meta fields changes.
      // Otherwise it's confusing for the user to click save in the editor,
      // but the changes here are discarded. This bypasses validation,
      // but we are okay with that.
      handleSave(event);
      openFileEditor(file);
    }
  }, i18n('editFile'))), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-FileCard-info"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_RenderMetaFields_js__WEBPACK_IMPORTED_MODULE_6__["default"], {
    computedMetaFields: computedMetaFields,
    requiredMetaFields: requiredMetaFields,
    updateMeta: updateMeta,
    form: form,
    formState: formState
  })), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-FileCard-actions"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    className: "uppy-u-reset uppy-c-btn uppy-c-btn-primary uppy-Dashboard-FileCard-actionsBtn" // If `form` attribute is supported, we want a submit button to trigger the form validation.
    // Otherwise, fallback to a classic button with a onClick event handler.
    ,
    type: "submit",
    form: form.id
  }, i18n('saveChanges')), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    className: "uppy-u-reset uppy-c-btn uppy-c-btn-link uppy-Dashboard-FileCard-actionsBtn",
    type: "button",
    onClick: handleCancel,
    form: form.id
  }, i18n('cancel')))));
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/Buttons/index.js":
/*!***********************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/Buttons/index.js ***!
  \***********************************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Buttons)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var _utils_copyToClipboard_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../utils/copyToClipboard.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/copyToClipboard.js");



function EditButton(_ref) {
  let {
    file,
    uploadInProgressOrComplete,
    metaFields,
    canEditFile,
    i18n,
    onClick
  } = _ref;

  if (!uploadInProgressOrComplete && metaFields && metaFields.length > 0 || !uploadInProgressOrComplete && canEditFile(file)) {
    return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
      className: "uppy-u-reset uppy-c-btn uppy-Dashboard-Item-action uppy-Dashboard-Item-action--edit",
      type: "button",
      "aria-label": i18n('editFileWithFilename', {
        file: file.meta.name
      }),
      title: i18n('editFileWithFilename', {
        file: file.meta.name
      }),
      onClick: () => onClick()
    }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      className: "uppy-c-icon",
      width: "14",
      height: "14",
      viewBox: "0 0 14 14"
    }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("g", {
      fillRule: "evenodd"
    }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
      d: "M1.5 10.793h2.793A1 1 0 0 0 5 10.5L11.5 4a1 1 0 0 0 0-1.414L9.707.793a1 1 0 0 0-1.414 0l-6.5 6.5A1 1 0 0 0 1.5 8v2.793zm1-1V8L9 1.5l1.793 1.793-6.5 6.5H2.5z",
      fillRule: "nonzero"
    }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("rect", {
      x: "1",
      y: "12.293",
      width: "11",
      height: "1",
      rx: ".5"
    }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
      fillRule: "nonzero",
      d: "M6.793 2.5L9.5 5.207l.707-.707L7.5 1.793z"
    }))));
  }

  return null;
}

function RemoveButton(_ref2) {
  let {
    i18n,
    onClick,
    file
  } = _ref2;
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    className: "uppy-u-reset uppy-Dashboard-Item-action uppy-Dashboard-Item-action--remove",
    type: "button",
    "aria-label": i18n('removeFile', {
      file: file.meta.name
    }),
    title: i18n('removeFile', {
      file: file.meta.name
    }),
    onClick: () => onClick()
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    "aria-hidden": "true",
    focusable: "false",
    className: "uppy-c-icon",
    width: "18",
    height: "18",
    viewBox: "0 0 18 18"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    d: "M9 0C4.034 0 0 4.034 0 9s4.034 9 9 9 9-4.034 9-9-4.034-9-9-9z"
  }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    fill: "#FFF",
    d: "M13 12.222l-.778.778L9 9.778 5.778 13 5 12.222 8.222 9 5 5.778 5.778 5 9 8.222 12.222 5l.778.778L9.778 9z"
  })));
}

const copyLinkToClipboard = (event, props) => {
  (0,_utils_copyToClipboard_js__WEBPACK_IMPORTED_MODULE_1__["default"])(props.file.uploadURL, props.i18n('copyLinkToClipboardFallback')).then(() => {
    props.uppy.log('Link copied to clipboard.');
    props.uppy.info(props.i18n('copyLinkToClipboardSuccess'), 'info', 3000);
  }).catch(props.uppy.log) // avoid losing focus
  .then(() => event.target.focus({
    preventScroll: true
  }));
};

function CopyLinkButton(props) {
  const {
    i18n
  } = props;
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    className: "uppy-u-reset uppy-Dashboard-Item-action uppy-Dashboard-Item-action--copyLink",
    type: "button",
    "aria-label": i18n('copyLink'),
    title: i18n('copyLink'),
    onClick: event => copyLinkToClipboard(event, props)
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    "aria-hidden": "true",
    focusable: "false",
    className: "uppy-c-icon",
    width: "14",
    height: "14",
    viewBox: "0 0 14 12"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    d: "M7.94 7.703a2.613 2.613 0 0 1-.626 2.681l-.852.851a2.597 2.597 0 0 1-1.849.766A2.616 2.616 0 0 1 2.764 7.54l.852-.852a2.596 2.596 0 0 1 2.69-.625L5.267 7.099a1.44 1.44 0 0 0-.833.407l-.852.851a1.458 1.458 0 0 0 1.03 2.486c.39 0 .755-.152 1.03-.426l.852-.852c.231-.231.363-.522.406-.824l1.04-1.038zm4.295-5.937A2.596 2.596 0 0 0 10.387 1c-.698 0-1.355.272-1.849.766l-.852.851a2.614 2.614 0 0 0-.624 2.688l1.036-1.036c.041-.304.173-.6.407-.833l.852-.852c.275-.275.64-.426 1.03-.426a1.458 1.458 0 0 1 1.03 2.486l-.852.851a1.442 1.442 0 0 1-.824.406l-1.04 1.04a2.596 2.596 0 0 0 2.683-.628l.851-.85a2.616 2.616 0 0 0 0-3.697zm-6.88 6.883a.577.577 0 0 0 .82 0l3.474-3.474a.579.579 0 1 0-.819-.82L5.355 7.83a.579.579 0 0 0 0 .819z"
  })));
}

function Buttons(props) {
  const {
    uppy,
    file,
    uploadInProgressOrComplete,
    canEditFile,
    metaFields,
    showLinkToFileUploadResult,
    showRemoveButton,
    i18n,
    toggleFileCard,
    openFileEditor
  } = props;

  const editAction = () => {
    if (metaFields && metaFields.length > 0) {
      toggleFileCard(true, file.id);
    } else {
      openFileEditor(file);
    }
  };

  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-Item-actionWrapper"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(EditButton, {
    i18n: i18n,
    file: file,
    uploadInProgressOrComplete: uploadInProgressOrComplete,
    canEditFile: canEditFile,
    metaFields: metaFields,
    onClick: editAction
  }), showLinkToFileUploadResult && file.uploadURL ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(CopyLinkButton, {
    file: file,
    uppy: uppy,
    i18n: i18n
  }) : null, showRemoveButton ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(RemoveButton, {
    i18n: i18n,
    file: file,
    uppy: uppy,
    onClick: () => props.uppy.removeFile(file.id, 'removed-by-user')
  }) : null);
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/FileInfo/index.js":
/*!************************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/FileInfo/index.js ***!
  \************************************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ FileInfo)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var _transloadit_prettier_bytes__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @transloadit/prettier-bytes */ "./src/jgDashboard/node_modules/@transloadit/prettier-bytes/prettierBytes.js");
/* harmony import */ var _uppy_utils_lib_truncateString__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @uppy/utils/lib/truncateString */ "./src/jgDashboard/node_modules/@uppy/utils/lib/truncateString.js");
/* harmony import */ var _MetaErrorMessage_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../MetaErrorMessage.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/MetaErrorMessage.js");





const renderFileName = props => {
  const {
    author,
    name
  } = props.file.meta;

  function getMaxNameLength() {
    if (props.isSingleFile && props.containerHeight >= 350) {
      return 90;
    }

    if (props.containerWidth <= 352) {
      return 35;
    }

    if (props.containerWidth <= 576) {
      return 60;
    } // When `author` is present, we want to make sure
    // the file name fits on one line so we can place
    // the author on the second line.


    return author ? 20 : 30;
  }

  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-Item-name",
    title: name
  }, (0,_uppy_utils_lib_truncateString__WEBPACK_IMPORTED_MODULE_2__["default"])(name, getMaxNameLength()));
};

const renderAuthor = props => {
  const {
    author
  } = props.file.meta;
  const {
    providerName
  } = props.file.remote;
  const dot = `\u00B7`;

  if (!author) {
    return null;
  }

  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-Item-author"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("a", {
    href: `${author.url}?utm_source=Companion&utm_medium=referral`,
    target: "_blank",
    rel: "noopener noreferrer"
  }, (0,_uppy_utils_lib_truncateString__WEBPACK_IMPORTED_MODULE_2__["default"])(author.name, 13)), providerName ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(preact__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, ` ${dot} `, providerName, ` ${dot} `) : null);
};

const renderFileSize = props => props.file.size && (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
  className: "uppy-Dashboard-Item-statusSize"
}, _transloadit_prettier_bytes__WEBPACK_IMPORTED_MODULE_1__(props.file.size));

const ReSelectButton = props => props.file.isGhost && (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("span", null, ' \u2022 ', (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
  className: "uppy-u-reset uppy-c-btn uppy-Dashboard-Item-reSelect",
  type: "button",
  onClick: props.toggleAddFilesPanel
}, props.i18n('reSelect')));

const ErrorButton = _ref => {
  let {
    file,
    onClick
  } = _ref;

  if (file.error) {
    return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
      className: "uppy-u-reset uppy-c-btn uppy-Dashboard-Item-errorDetails",
      "aria-label": file.error,
      "data-microtip-position": "bottom",
      "data-microtip-size": "medium",
      onClick: onClick,
      type: "button"
    }, "?");
  }

  return null;
};

function FileInfo(props) {
  const {
    file
  } = props;
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-Item-fileInfo",
    "data-uppy-file-source": file.source
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-Item-fileName"
  }, renderFileName(props), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(ErrorButton, {
    file: props.file // eslint-disable-next-line no-alert
    ,
    onClick: () => alert(props.file.error) // TODO: move to a custom alert implementation

  })), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-Item-status"
  }, renderAuthor(props), renderFileSize(props), ReSelectButton(props)), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_MetaErrorMessage_js__WEBPACK_IMPORTED_MODULE_3__["default"], {
    file: props.file,
    i18n: props.i18n,
    toggleFileCard: props.toggleFileCard,
    metaFields: props.metaFields
  }));
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/FilePreviewAndLink/index.js":
/*!**********************************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/FilePreviewAndLink/index.js ***!
  \**********************************************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ FilePreviewAndLink)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var _FilePreview_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../FilePreview.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FilePreview.js");
/* harmony import */ var _MetaErrorMessage_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../MetaErrorMessage.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/MetaErrorMessage.js");
/* harmony import */ var _utils_getFileTypeIcon_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../utils/getFileTypeIcon.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/getFileTypeIcon.js");




function FilePreviewAndLink(props) {
  const {
    file,
    i18n,
    toggleFileCard,
    metaFields,
    showLinkToFileUploadResult
  } = props;
  const white = 'rgba(255, 255, 255, 0.5)';
  const previewBackgroundColor = file.preview ? white : (0,_utils_getFileTypeIcon_js__WEBPACK_IMPORTED_MODULE_3__["default"])(props.file.type).color;
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-Item-previewInnerWrap",
    style: {
      backgroundColor: previewBackgroundColor
    }
  }, showLinkToFileUploadResult && file.uploadURL && (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("a", {
    className: "uppy-Dashboard-Item-previewLink",
    href: file.uploadURL,
    rel: "noreferrer noopener",
    target: "_blank",
    "aria-label": file.meta.name
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("span", {
    hidden: true
  }, file.meta.name)), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_FilePreview_js__WEBPACK_IMPORTED_MODULE_1__["default"], {
    file: file
  }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_MetaErrorMessage_js__WEBPACK_IMPORTED_MODULE_2__["default"], {
    file: file,
    i18n: i18n,
    toggleFileCard: toggleFileCard,
    metaFields: metaFields
  }));
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/FileProgress/index.js":
/*!****************************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/FileProgress/index.js ***!
  \****************************************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ FileProgress)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");


function onPauseResumeCancelRetry(props) {
  if (props.isUploaded) return;

  if (props.error && !props.hideRetryButton) {
    props.uppy.retryUpload(props.file.id);
    return;
  }

  if (props.resumableUploads && !props.hidePauseResumeButton) {
    props.uppy.pauseResume(props.file.id);
  } else if (props.individualCancellation && !props.hideCancelButton) {
    props.uppy.removeFile(props.file.id);
  }
}

function progressIndicatorTitle(props) {
  if (props.isUploaded) {
    return props.i18n('uploadComplete');
  }

  if (props.error) {
    return props.i18n('retryUpload');
  }

  if (props.resumableUploads) {
    if (props.file.isPaused) {
      return props.i18n('resumeUpload');
    }

    return props.i18n('pauseUpload');
  }

  if (props.individualCancellation) {
    return props.i18n('cancelUpload');
  }

  return '';
}

function ProgressIndicatorButton(props) {
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-Item-progress"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    className: "uppy-u-reset uppy-c-btn uppy-Dashboard-Item-progressIndicator",
    type: "button",
    "aria-label": progressIndicatorTitle(props),
    title: progressIndicatorTitle(props),
    onClick: () => onPauseResumeCancelRetry(props)
  }, props.children));
}

function ProgressCircleContainer(_ref) {
  let {
    children
  } = _ref;
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    "aria-hidden": "true",
    focusable: "false",
    width: "70",
    height: "70",
    viewBox: "0 0 36 36",
    className: "uppy-c-icon uppy-Dashboard-Item-progressIcon--circle"
  }, children);
}

function ProgressCircle(_ref2) {
  let {
    progress
  } = _ref2;
  // circle length equals 2 * PI * R
  const circleLength = 2 * Math.PI * 15;
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("g", null, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("circle", {
    className: "uppy-Dashboard-Item-progressIcon--bg",
    r: "15",
    cx: "18",
    cy: "18",
    "stroke-width": "2",
    fill: "none"
  }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("circle", {
    className: "uppy-Dashboard-Item-progressIcon--progress",
    r: "15",
    cx: "18",
    cy: "18",
    transform: "rotate(-90, 18, 18)",
    fill: "none",
    "stroke-width": "2",
    "stroke-dasharray": circleLength,
    "stroke-dashoffset": circleLength - circleLength / 100 * progress
  }));
}

function FileProgress(props) {
  // Nothing if upload has not started
  if (!props.file.progress.uploadStarted) {
    return null;
  } // Green checkmark when complete


  if (props.isUploaded) {
    return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
      className: "uppy-Dashboard-Item-progress"
    }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
      className: "uppy-Dashboard-Item-progressIndicator"
    }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(ProgressCircleContainer, null, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("circle", {
      r: "15",
      cx: "18",
      cy: "18",
      fill: "#1bb240"
    }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("polygon", {
      className: "uppy-Dashboard-Item-progressIcon--check",
      transform: "translate(2, 3)",
      points: "14 22.5 7 15.2457065 8.99985857 13.1732815 14 18.3547104 22.9729883 9 25 11.1005634"
    }))));
  }

  if (props.recoveredState) {
    return undefined;
  } // Retry button for error


  if (props.error && !props.hideRetryButton) {
    return (// eslint-disable-next-line react/jsx-props-no-spreading
      (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(ProgressIndicatorButton, props, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        className: "uppy-c-icon uppy-Dashboard-Item-progressIcon--retry",
        width: "28",
        height: "31",
        viewBox: "0 0 16 19"
      }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
        d: "M16 11a8 8 0 1 1-8-8v2a6 6 0 1 0 6 6h2z"
      }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
        d: "M7.9 3H10v2H7.9z"
      }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
        d: "M8.536.5l3.535 3.536-1.414 1.414L7.12 1.914z"
      }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
        d: "M10.657 2.621l1.414 1.415L8.536 7.57 7.12 6.157z"
      })))
    );
  } // Pause/resume button for resumable uploads


  if (props.resumableUploads && !props.hidePauseResumeButton) {
    return (// eslint-disable-next-line react/jsx-props-no-spreading
      (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(ProgressIndicatorButton, props, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(ProgressCircleContainer, null, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(ProgressCircle, {
        progress: props.file.progress.percentage
      }), props.file.isPaused ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("polygon", {
        className: "uppy-Dashboard-Item-progressIcon--play",
        transform: "translate(3, 3)",
        points: "12 20 12 10 20 15"
      }) : (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("g", {
        className: "uppy-Dashboard-Item-progressIcon--pause",
        transform: "translate(14.5, 13)"
      }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("rect", {
        x: "0",
        y: "0",
        width: "2",
        height: "10",
        rx: "0"
      }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("rect", {
        x: "5",
        y: "0",
        width: "2",
        height: "10",
        rx: "0"
      }))))
    );
  } // Cancel button for non-resumable uploads if individualCancellation is supported (not bundled)


  if (!props.resumableUploads && props.individualCancellation && !props.hideCancelButton) {
    return (// eslint-disable-next-line react/jsx-props-no-spreading
      (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(ProgressIndicatorButton, props, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(ProgressCircleContainer, null, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(ProgressCircle, {
        progress: props.file.progress.percentage
      }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("polygon", {
        className: "cancel",
        transform: "translate(2, 2)",
        points: "19.8856516 11.0625 16 14.9481516 12.1019737 11.0625 11.0625 12.1143484 14.9481516 16 11.0625 19.8980263 12.1019737 20.9375 16 17.0518484 19.8856516 20.9375 20.9375 19.8980263 17.0518484 16 20.9375 12"
      })))
    );
  } // Just progress when buttons are disabled


  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-Item-progress"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-Item-progressIndicator"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(ProgressCircleContainer, null, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(ProgressCircle, {
    progress: props.file.progress.percentage
  }))));
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/MetaErrorMessage.js":
/*!**************************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/MetaErrorMessage.js ***!
  \**************************************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ renderMissingMetaFieldsError)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");


const metaFieldIdToName = (metaFieldId, metaFields) => {
  const fields = typeof metaFields === 'function' ? metaFields() : metaFields;
  const field = fields.filter(f => f.id === metaFieldId);
  return field[0].name;
};

function renderMissingMetaFieldsError(props) {
  const {
    file,
    toggleFileCard,
    i18n,
    metaFields
  } = props;
  const {
    missingRequiredMetaFields
  } = file;

  if (!(missingRequiredMetaFields != null && missingRequiredMetaFields.length)) {
    return null;
  }

  const metaFieldsString = missingRequiredMetaFields.map(missingMetaField => metaFieldIdToName(missingMetaField, metaFields)).join(', ');
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-Item-errorMessage"
  }, i18n('missingRequiredMetaFields', {
    smart_count: missingRequiredMetaFields.length,
    fields: metaFieldsString
  }), ' ', (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    type: "button",
    class: "uppy-u-reset uppy-Dashboard-Item-errorMessageBtn",
    onClick: () => toggleFileCard(true, file.id)
  }, i18n('editFile')));
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/index.js":
/*!***************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/index.js ***!
  \***************************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ FileItem)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! classnames */ "./src/jgDashboard/node_modules/classnames/index.js");
/* harmony import */ var is_shallow_equal__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! is-shallow-equal */ "./src/jgDashboard/node_modules/is-shallow-equal/index.js");
/* harmony import */ var _FilePreviewAndLink_index_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./FilePreviewAndLink/index.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/FilePreviewAndLink/index.js");
/* harmony import */ var _FileProgress_index_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./FileProgress/index.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/FileProgress/index.js");
/* harmony import */ var _FileInfo_index_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./FileInfo/index.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/FileInfo/index.js");
/* harmony import */ var _Buttons_index_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./Buttons/index.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/Buttons/index.js");







class FileItem extends preact__WEBPACK_IMPORTED_MODULE_0__.Component {
  componentDidMount() {
    const {
      file
    } = this.props;

    if (!file.preview) {
      this.props.handleRequestThumbnail(file);
    }
  }

  shouldComponentUpdate(nextProps) {
    return !is_shallow_equal__WEBPACK_IMPORTED_MODULE_2__(this.props, nextProps);
  } // VirtualList mounts FileItems again and they emit `thumbnail:request`
  // Otherwise thumbnails are broken or missing after Golden Retriever restores files


  componentDidUpdate() {
    const {
      file
    } = this.props;

    if (!file.preview) {
      this.props.handleRequestThumbnail(file);
    }
  }

  componentWillUnmount() {
    const {
      file
    } = this.props;

    if (!file.preview) {
      this.props.handleCancelThumbnail(file);
    }
  }

  render() {
    const {
      file
    } = this.props;
    const isProcessing = file.progress.preprocess || file.progress.postprocess;
    const isUploaded = file.progress.uploadComplete && !isProcessing && !file.error;
    const uploadInProgressOrComplete = file.progress.uploadStarted || isProcessing;
    const uploadInProgress = file.progress.uploadStarted && !file.progress.uploadComplete || isProcessing;
    const error = file.error || false; // File that Golden Retriever was able to partly restore (only meta, not blob),
    // users still need to re-add it, so it’s a ghost

    const {
      isGhost
    } = file;
    let showRemoveButton = this.props.individualCancellation ? !isUploaded : !uploadInProgress && !isUploaded;

    if (isUploaded && this.props.showRemoveButtonAfterComplete) {
      showRemoveButton = true;
    }

    const dashboardItemClass = classnames__WEBPACK_IMPORTED_MODULE_1__({
      'uppy-Dashboard-Item': true,
      'is-inprogress': uploadInProgress && !this.props.recoveredState,
      'is-processing': isProcessing,
      'is-complete': isUploaded,
      'is-error': !!error,
      'is-resumable': this.props.resumableUploads,
      'is-noIndividualCancellation': !this.props.individualCancellation,
      'is-ghost': isGhost
    });
    return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
      className: dashboardItemClass,
      id: `uppy_${file.id}`,
      role: this.props.role
    }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
      className: "uppy-Dashboard-Item-preview"
    }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_FilePreviewAndLink_index_js__WEBPACK_IMPORTED_MODULE_3__["default"], {
      file: file,
      showLinkToFileUploadResult: this.props.showLinkToFileUploadResult,
      i18n: this.props.i18n,
      toggleFileCard: this.props.toggleFileCard,
      metaFields: this.props.metaFields
    }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_FileProgress_index_js__WEBPACK_IMPORTED_MODULE_4__["default"], {
      uppy: this.props.uppy,
      file: file,
      error: error,
      isUploaded: isUploaded,
      hideRetryButton: this.props.hideRetryButton,
      hideCancelButton: this.props.hideCancelButton,
      hidePauseResumeButton: this.props.hidePauseResumeButton,
      recoveredState: this.props.recoveredState,
      showRemoveButtonAfterComplete: this.props.showRemoveButtonAfterComplete,
      resumableUploads: this.props.resumableUploads,
      individualCancellation: this.props.individualCancellation,
      i18n: this.props.i18n
    })), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
      className: "uppy-Dashboard-Item-fileInfoAndButtons"
    }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_FileInfo_index_js__WEBPACK_IMPORTED_MODULE_5__["default"], {
      file: file,
      id: this.props.id,
      acquirers: this.props.acquirers,
      containerWidth: this.props.containerWidth,
      containerHeight: this.props.containerHeight,
      i18n: this.props.i18n,
      toggleAddFilesPanel: this.props.toggleAddFilesPanel,
      toggleFileCard: this.props.toggleFileCard,
      metaFields: this.props.metaFields,
      isSingleFile: this.props.isSingleFile
    }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_Buttons_index_js__WEBPACK_IMPORTED_MODULE_6__["default"], {
      file: file,
      metaFields: this.props.metaFields,
      showLinkToFileUploadResult: this.props.showLinkToFileUploadResult,
      showRemoveButton: showRemoveButton,
      canEditFile: this.props.canEditFile,
      uploadInProgressOrComplete: uploadInProgressOrComplete,
      toggleFileCard: this.props.toggleFileCard,
      openFileEditor: this.props.openFileEditor,
      uppy: this.props.uppy,
      i18n: this.props.i18n
    })));
  }

}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileList.js":
/*!*********************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileList.js ***!
  \*********************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var preact_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! preact/hooks */ "./src/jgDashboard/node_modules/preact/hooks/dist/hooks.module.js");
/* harmony import */ var _FileItem_index_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FileItem/index.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FileItem/index.js");
/* harmony import */ var _VirtualList_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./VirtualList.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/VirtualList.js");





function chunks(list, size) {
  const chunked = [];
  let currentChunk = [];
  list.forEach(item => {
    if (currentChunk.length < size) {
      currentChunk.push(item);
    } else {
      chunked.push(currentChunk);
      currentChunk = [item];
    }
  });
  if (currentChunk.length) chunked.push(currentChunk);
  return chunked;
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_ref => {
  let {
    id,
    error,
    i18n,
    uppy,
    files,
    acquirers,
    resumableUploads,
    hideRetryButton,
    hidePauseResumeButton,
    hideCancelButton,
    showLinkToFileUploadResult,
    showRemoveButtonAfterComplete,
    isWide,
    metaFields,
    isSingleFile,
    toggleFileCard,
    handleRequestThumbnail,
    handleCancelThumbnail,
    recoveredState,
    individualCancellation,
    itemsPerRow,
    openFileEditor,
    canEditFile,
    toggleAddFilesPanel,
    containerWidth,
    containerHeight
  } = _ref;
  // It's not great that this is hardcoded!
  // It's ESPECIALLY not great that this is checking against `itemsPerRow`!
  const rowHeight = itemsPerRow === 1 // Mobile
  ? 71 // 190px height + 2 * 5px margin
  : 200; // Sort files by file.isGhost, ghost files first, only if recoveredState is present

  const rows = (0,preact_hooks__WEBPACK_IMPORTED_MODULE_1__.useMemo)(() => {
    const sortByGhostComesFirst = (file1, file2) => files[file2].isGhost - files[file1].isGhost;

    const fileIds = Object.keys(files);
    if (recoveredState) fileIds.sort(sortByGhostComesFirst);
    return chunks(fileIds, itemsPerRow);
  }, [files, itemsPerRow, recoveredState]);

  const renderRow = row => // The `role="presentation` attribute ensures that the list items are properly
  // associated with the `VirtualList` element.
  // We use the first file ID as the key—this should not change across scroll rerenders
  (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    class: "uppy-Dashboard-filesInner",
    role: "presentation",
    key: row[0]
  }, row.map(fileID => (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_FileItem_index_js__WEBPACK_IMPORTED_MODULE_2__["default"], {
    key: fileID,
    uppy: uppy // FIXME This is confusing, it's actually the Dashboard's plugin ID
    ,
    id: id,
    error: error // TODO move this to context
    ,
    i18n: i18n // features
    ,
    acquirers: acquirers,
    resumableUploads: resumableUploads,
    individualCancellation: individualCancellation // visual options
    ,
    hideRetryButton: hideRetryButton,
    hidePauseResumeButton: hidePauseResumeButton,
    hideCancelButton: hideCancelButton,
    showLinkToFileUploadResult: showLinkToFileUploadResult,
    showRemoveButtonAfterComplete: showRemoveButtonAfterComplete,
    isWide: isWide,
    metaFields: metaFields,
    recoveredState: recoveredState,
    isSingleFile: isSingleFile,
    containerWidth: containerWidth,
    containerHeight: containerHeight // callbacks
    ,
    toggleFileCard: toggleFileCard,
    handleRequestThumbnail: handleRequestThumbnail,
    handleCancelThumbnail: handleCancelThumbnail,
    role: "listitem",
    openFileEditor: openFileEditor,
    canEditFile: canEditFile,
    toggleAddFilesPanel: toggleAddFilesPanel,
    file: files[fileID]
  })));

  if (isSingleFile) {
    return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
      class: "uppy-Dashboard-files"
    }, renderRow(rows[0]));
  }

  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_VirtualList_js__WEBPACK_IMPORTED_MODULE_3__["default"], {
    class: "uppy-Dashboard-files",
    role: "list",
    data: rows,
    renderRow: renderRow,
    rowHeight: rowHeight
  });
});

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FilePreview.js":
/*!************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/FilePreview.js ***!
  \************************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ FilePreview)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var _utils_getFileTypeIcon_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/getFileTypeIcon.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/getFileTypeIcon.js");


function FilePreview(props) {
  const {
    file
  } = props;

  if (file.preview) {
    return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("img", {
      className: "uppy-Dashboard-Item-previewImg",
      alt: file.name,
      src: file.preview
    });
  }

  const {
    color,
    icon
  } = (0,_utils_getFileTypeIcon_js__WEBPACK_IMPORTED_MODULE_1__["default"])(file.type);
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-Dashboard-Item-previewIconWrap"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("span", {
    className: "uppy-Dashboard-Item-previewIcon",
    style: {
      color
    }
  }, icon), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    "aria-hidden": "true",
    focusable: "false",
    className: "uppy-Dashboard-Item-previewIconBg",
    width: "58",
    height: "76",
    viewBox: "0 0 58 76"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("rect", {
    fill: "#FFF",
    width: "58",
    height: "76",
    rx: "3",
    fillRule: "evenodd"
  })));
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/PickerPanelContent.js":
/*!*******************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/PickerPanelContent.js ***!
  \*******************************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! classnames */ "./src/jgDashboard/node_modules/classnames/index.js");
/* harmony import */ var _utils_ignoreEvent_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/ignoreEvent.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/ignoreEvent.js");




function PickerPanelContent(_ref) {
  let {
    activePickerPanel,
    className,
    hideAllPanels,
    i18n,
    state,
    uppy
  } = _ref;
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: classnames__WEBPACK_IMPORTED_MODULE_1__('uppy-DashboardContent-panel', className),
    role: "tabpanel",
    "data-uppy-panelType": "PickerPanel",
    id: `uppy-DashboardContent-panel--${activePickerPanel.id}`,
    onDragOver: _utils_ignoreEvent_js__WEBPACK_IMPORTED_MODULE_2__["default"],
    onDragLeave: _utils_ignoreEvent_js__WEBPACK_IMPORTED_MODULE_2__["default"],
    onDrop: _utils_ignoreEvent_js__WEBPACK_IMPORTED_MODULE_2__["default"],
    onPaste: _utils_ignoreEvent_js__WEBPACK_IMPORTED_MODULE_2__["default"]
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-DashboardContent-bar"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-DashboardContent-title",
    role: "heading",
    "aria-level": "1"
  }, i18n('importFrom', {
    name: activePickerPanel.name
  })), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    className: "uppy-DashboardContent-back",
    type: "button",
    onClick: hideAllPanels
  }, i18n('cancel'))), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-DashboardContent-panelBody"
  }, uppy.getPlugin(activePickerPanel.id).render(state)));
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (PickerPanelContent);

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/PickerPanelTopBar.js":
/*!******************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/PickerPanelTopBar.js ***!
  \******************************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");

const uploadStates = {
  STATE_ERROR: 'error',
  STATE_WAITING: 'waiting',
  STATE_PREPROCESSING: 'preprocessing',
  STATE_UPLOADING: 'uploading',
  STATE_POSTPROCESSING: 'postprocessing',
  STATE_COMPLETE: 'complete',
  STATE_PAUSED: 'paused'
};

function getUploadingState(isAllErrored, isAllComplete, isAllPaused, files) {
  if (files === void 0) {
    files = {};
  }

  if (isAllErrored) {
    return uploadStates.STATE_ERROR;
  }

  if (isAllComplete) {
    return uploadStates.STATE_COMPLETE;
  }

  if (isAllPaused) {
    return uploadStates.STATE_PAUSED;
  }

  let state = uploadStates.STATE_WAITING;
  const fileIDs = Object.keys(files);

  for (let i = 0; i < fileIDs.length; i++) {
    const {
      progress
    } = files[fileIDs[i]]; // If ANY files are being uploaded right now, show the uploading state.

    if (progress.uploadStarted && !progress.uploadComplete) {
      return uploadStates.STATE_UPLOADING;
    } // If files are being preprocessed AND postprocessed at this time, we show the
    // preprocess state. If any files are being uploaded we show uploading.


    if (progress.preprocess && state !== uploadStates.STATE_UPLOADING) {
      state = uploadStates.STATE_PREPROCESSING;
    } // If NO files are being preprocessed or uploaded right now, but some files are
    // being postprocessed, show the postprocess state.


    if (progress.postprocess && state !== uploadStates.STATE_UPLOADING && state !== uploadStates.STATE_PREPROCESSING) {
      state = uploadStates.STATE_POSTPROCESSING;
    }
  }

  return state;
}

function UploadStatus(_ref) {
  let {
    files,
    i18n,
    isAllComplete,
    isAllErrored,
    isAllPaused,
    inProgressNotPausedFiles,
    newFiles,
    processingFiles
  } = _ref;
  const uploadingState = getUploadingState(isAllErrored, isAllComplete, isAllPaused, files);

  switch (uploadingState) {
    case 'uploading':
      return i18n('uploadingXFiles', {
        smart_count: inProgressNotPausedFiles.length
      });

    case 'preprocessing':
    case 'postprocessing':
      return i18n('processingXFiles', {
        smart_count: processingFiles.length
      });

    case 'paused':
      return i18n('uploadPaused');

    case 'waiting':
      return i18n('xFilesSelected', {
        smart_count: newFiles.length
      });

    case 'complete':
      return i18n('uploadComplete');

    case 'error':
      return i18n('error');

    default:
  }
}

function PanelTopBar(props) {
  const {
    i18n,
    isAllComplete,
    hideCancelButton,
    maxNumberOfFiles,
    toggleAddFilesPanel,
    uppy
  } = props;
  let {
    allowNewUpload
  } = props; // TODO maybe this should be done in ../Dashboard.jsx, then just pass that down as `allowNewUpload`

  if (allowNewUpload && maxNumberOfFiles) {
    // eslint-disable-next-line react/destructuring-assignment
    allowNewUpload = props.totalFileCount < props.maxNumberOfFiles;
  }

  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-DashboardContent-bar"
  }, !isAllComplete && !hideCancelButton ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    className: "uppy-DashboardContent-back",
    type: "button",
    onClick: () => uppy.cancelAll()
  }, i18n('cancel')) : (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", null), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-DashboardContent-title",
    role: "heading",
    "aria-level": "1"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(UploadStatus, props)), allowNewUpload ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    className: "uppy-DashboardContent-addMore",
    type: "button",
    "aria-label": i18n('addMoreFiles'),
    title: i18n('addMoreFiles'),
    onClick: () => toggleAddFilesPanel(true)
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    "aria-hidden": "true",
    focusable: "false",
    className: "uppy-c-icon",
    width: "15",
    height: "15",
    viewBox: "0 0 15 15"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    d: "M8 6.5h6a.5.5 0 0 1 .5.5v.5a.5.5 0 0 1-.5.5H8v6a.5.5 0 0 1-.5.5H7a.5.5 0 0 1-.5-.5V8h-6a.5.5 0 0 1-.5-.5V7a.5.5 0 0 1 .5-.5h6v-6A.5.5 0 0 1 7 0h.5a.5.5 0 0 1 .5.5v6z"
  })), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("span", {
    className: "uppy-DashboardContent-addMoreCaption"
  }, i18n('addMore'))) : (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", null));
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (PanelTopBar);

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/Slide.js":
/*!******************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/Slide.js ***!
  \******************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! classnames */ "./src/jgDashboard/node_modules/classnames/index.js");


const transitionName = 'uppy-transition-slideDownUp';
const duration = 250;
/**
 * Vertical slide transition.
 *
 * This can take a _single_ child component, which _must_ accept a `className` prop.
 *
 * Currently this is specific to the `uppy-transition-slideDownUp` transition,
 * but it should be simple to extend this for any type of single-element
 * transition by setting the CSS name and duration as props.
 */

class Slide extends preact__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor(props) {
    super(props);
    this.state = {
      cachedChildren: null,
      className: ''
    };
  } // TODO: refactor to stable lifecycle method
  // eslint-disable-next-line


  componentWillUpdate(nextProps) {
    const {
      cachedChildren
    } = this.state;
    const child = (0,preact__WEBPACK_IMPORTED_MODULE_0__.toChildArray)(nextProps.children)[0];
    if (cachedChildren === child) return null;
    const patch = {
      cachedChildren: child
    }; // Enter transition

    if (child && !cachedChildren) {
      patch.className = `${transitionName}-enter`;
      cancelAnimationFrame(this.animationFrame);
      clearTimeout(this.leaveTimeout);
      this.leaveTimeout = undefined;
      this.animationFrame = requestAnimationFrame(() => {
        // Force it to render before we add the active class
        // this.base.getBoundingClientRect()
        this.setState({
          className: `${transitionName}-enter ${transitionName}-enter-active`
        });
        this.enterTimeout = setTimeout(() => {
          this.setState({
            className: ''
          });
        }, duration);
      });
    } // Leave transition


    if (cachedChildren && !child && this.leaveTimeout === undefined) {
      patch.cachedChildren = cachedChildren;
      patch.className = `${transitionName}-leave`;
      cancelAnimationFrame(this.animationFrame);
      clearTimeout(this.enterTimeout);
      this.enterTimeout = undefined;
      this.animationFrame = requestAnimationFrame(() => {
        this.setState({
          className: `${transitionName}-leave ${transitionName}-leave-active`
        });
        this.leaveTimeout = setTimeout(() => {
          this.setState({
            cachedChildren: null,
            className: ''
          });
        }, duration);
      });
    } // eslint-disable-next-line


    this.setState(patch);
  }

  render() {
    const {
      cachedChildren,
      className
    } = this.state;

    if (!cachedChildren) {
      return null;
    }

    return (0,preact__WEBPACK_IMPORTED_MODULE_0__.cloneElement)(cachedChildren, {
      className: classnames__WEBPACK_IMPORTED_MODULE_1__(className, cachedChildren.props.className)
    });
  }

}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Slide);

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/VirtualList.js":
/*!************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/components/VirtualList.js ***!
  \************************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

/**
 * Adapted from preact-virtual-list: https://github.com/developit/preact-virtual-list
 *
 * © 2016 Jason Miller
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * Adaptations:
 * - Added role=presentation to helper elements
 * - Tweaked styles for Uppy's Dashboard use case
 */

const STYLE_INNER = {
  position: 'relative',
  // Disabled for our use case: the wrapper elements around FileList already deal with overflow,
  // and this additional property would hide things that we want to show.
  //
  // overflow: 'hidden',
  width: '100%',
  minHeight: '100%'
};
const STYLE_CONTENT = {
  position: 'absolute',
  top: 0,
  left: 0,
  // Because the `top` value gets set to some offset, this `height` being 100% would make the scrollbar
  // stretch far beyond the content. For our use case, the content div actually can get its height from
  // the elements inside it, so we don't need to specify a `height` property at all.
  //
  // height: '100%',
  width: '100%',
  overflow: 'visible'
};

class VirtualList extends preact__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor(props) {
    super(props); // The currently focused node, used to retain focus when the visible rows change.
    // To avoid update loops, this should not cause state updates, so it's kept as a plain property.

    this.handleScroll = () => {
      this.setState({
        offset: this.base.scrollTop
      });
    };

    this.handleResize = () => {
      this.resize();
    };

    this.focusElement = null;
    this.state = {
      offset: 0,
      height: 0
    };
  }

  componentDidMount() {
    this.resize();
    window.addEventListener('resize', this.handleResize);
  } // TODO: refactor to stable lifecycle method
  // eslint-disable-next-line


  componentWillUpdate() {
    if (this.base.contains(document.activeElement)) {
      this.focusElement = document.activeElement;
    }
  }

  componentDidUpdate() {
    // Maintain focus when rows are added and removed.
    if (this.focusElement && this.focusElement.parentNode && document.activeElement !== this.focusElement) {
      this.focusElement.focus();
    }

    this.focusElement = null;
    this.resize();
  }

  componentWillUnmount() {
    window.removeEventListener('resize', this.handleResize);
  }

  resize() {
    const {
      height
    } = this.state;

    if (height !== this.base.offsetHeight) {
      this.setState({
        height: this.base.offsetHeight
      });
    }
  }

  render(_ref) {
    let {
      data,
      rowHeight,
      renderRow,
      overscanCount = 10,
      ...props
    } = _ref;
    const {
      offset,
      height
    } = this.state; // first visible row index

    let start = Math.floor(offset / rowHeight); // actual number of visible rows (without overscan)

    let visibleRowCount = Math.floor(height / rowHeight); // Overscan: render blocks of rows modulo an overscan row count
    // This dramatically reduces DOM writes during scrolling

    if (overscanCount) {
      start = Math.max(0, start - start % overscanCount);
      visibleRowCount += overscanCount;
    } // last visible + overscan row index + padding to allow keyboard focus to travel past the visible area


    const end = start + visibleRowCount + 4; // data slice currently in viewport plus overscan items

    const selection = data.slice(start, end);
    const styleInner = { ...STYLE_INNER,
      height: data.length * rowHeight
    };
    const styleContent = { ...STYLE_CONTENT,
      top: start * rowHeight
    }; // The `role="presentation"` attributes ensure that these wrapper elements are not treated as list
    // items by accessibility and outline tools.

    return (// eslint-disable-next-line react/jsx-props-no-spreading
      (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", _extends({
        onScroll: this.handleScroll
      }, props), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
        role: "presentation",
        style: styleInner
      }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
        role: "presentation",
        style: styleContent
      }, selection.map(renderRow))))
    );
  }

}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (VirtualList);

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/locale.js":
/*!********************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/locale.js ***!
  \********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  strings: {
    // When `inline: false`, used as the screen reader label for the button that closes the modal.
    closeModal: 'Close Modal',
    // Used as the screen reader label for the plus (+) button that shows the “Add more files” screen
    addMoreFiles: 'Add more files',
    addingMoreFiles: 'Adding more files',
    // Used as the header for import panels, e.g., “Import from Google Drive”.
    importFrom: 'Import from %{name}',
    // When `inline: false`, used as the screen reader label for the dashboard modal.
    dashboardWindowTitle: 'Uppy Dashboard Window (Press escape to close)',
    // When `inline: true`, used as the screen reader label for the dashboard area.
    dashboardTitle: 'Uppy Dashboard',
    // Shown in the Informer when a link to a file was copied to the clipboard.
    copyLinkToClipboardSuccess: 'Link copied to clipboard.',
    // Used when a link cannot be copied automatically — the user has to select the text from the
    // input element below this string.
    copyLinkToClipboardFallback: 'Copy the URL below',
    // Used as the hover title and screen reader label for buttons that copy a file link.
    copyLink: 'Copy link',
    back: 'Back',
    // Used as the screen reader label for buttons that remove a file.
    removeFile: 'Remove file',
    // Used as the screen reader label for buttons that open the metadata editor panel for a file.
    editFile: 'Edit file',
    // Shown in the panel header for the metadata editor. Rendered as “Editing image.png”.
    editing: 'Editing %{file}',
    // Shown on the main upload screen when an upload error occurs
    error: 'Error',
    // Used as the screen reader label for the button that saves metadata edits and returns to the
    // file list view.
    finishEditingFile: 'Finish editing file',
    saveChanges: 'Save changes',
    // Used as the label for the tab button that opens the system file selection dialog.
    myDevice: 'My Device',
    dropHint: 'Drop your files here',
    // Used as the hover text and screen reader label for file progress indicators when
    // they have been fully uploaded.
    uploadComplete: 'Upload complete',
    uploadPaused: 'Upload paused',
    // Used as the hover text and screen reader label for the buttons to resume paused uploads.
    resumeUpload: 'Resume upload',
    // Used as the hover text and screen reader label for the buttons to pause uploads.
    pauseUpload: 'Pause upload',
    // Used as the hover text and screen reader label for the buttons to retry failed uploads.
    retryUpload: 'Retry upload',
    // Used as the hover text and screen reader label for the buttons to cancel uploads.
    cancelUpload: 'Cancel upload',
    // Used in a title, how many files are currently selected
    xFilesSelected: {
      0: '%{smart_count} file selected',
      1: '%{smart_count} files selected'
    },
    uploadingXFiles: {
      0: 'Uploading %{smart_count} file',
      1: 'Uploading %{smart_count} files'
    },
    processingXFiles: {
      0: 'Processing %{smart_count} file',
      1: 'Processing %{smart_count} files'
    },
    // The "powered by Uppy" link at the bottom of the Dashboard.
    poweredBy: 'Powered by %{uppy}',
    addMore: 'Add more',
    editFileWithFilename: 'Edit file %{file}',
    save: 'Save',
    cancel: 'Cancel',
    dropPasteFiles: 'Drop files here or %{browseFiles}',
    dropPasteFolders: 'Drop files here or %{browseFolders}',
    dropPasteBoth: 'Drop files here, %{browseFiles} or %{browseFolders}',
    dropPasteImportFiles: 'Drop files here, %{browseFiles} or import from:',
    dropPasteImportFolders: 'Drop files here, %{browseFolders} or import from:',
    dropPasteImportBoth: 'Drop files here, %{browseFiles}, %{browseFolders} or import from:',
    importFiles: 'Import files from:',
    browseFiles: 'browse files',
    browseFolders: 'browse folders',
    recoveredXFiles: {
      0: 'We could not fully recover 1 file. Please re-select it and resume the upload.',
      1: 'We could not fully recover %{smart_count} files. Please re-select them and resume the upload.'
    },
    recoveredAllFiles: 'We restored all files. You can now resume the upload.',
    sessionRestored: 'Session restored',
    reSelect: 'Re-select',
    missingRequiredMetaFields: {
      0: 'Missing required meta field: %{fields}.',
      1: 'Missing required meta fields: %{fields}.'
    },
    // Used for native device camera buttons on mobile
    takePictureBtn: 'Take Picture',
    recordVideoBtn: 'Record Video'
  }
});

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/copyToClipboard.js":
/*!***********************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/copyToClipboard.js ***!
  \***********************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ copyToClipboard)
/* harmony export */ });
/**
 * Copies text to clipboard by creating an almost invisible textarea,
 * adding text there, then running execCommand('copy').
 * Falls back to prompt() when the easy way fails (hello, Safari!)
 * From http://stackoverflow.com/a/30810322
 *
 * @param {string} textToCopy
 * @param {string} fallbackString
 * @returns {Promise}
 */
function copyToClipboard(textToCopy, fallbackString) {
  if (fallbackString === void 0) {
    fallbackString = 'Copy the URL below';
  }

  return new Promise(resolve => {
    const textArea = document.createElement('textarea');
    textArea.setAttribute('style', {
      position: 'fixed',
      top: 0,
      left: 0,
      width: '2em',
      height: '2em',
      padding: 0,
      border: 'none',
      outline: 'none',
      boxShadow: 'none',
      background: 'transparent'
    });
    textArea.value = textToCopy;
    document.body.appendChild(textArea);
    textArea.select();

    const magicCopyFailed = () => {
      document.body.removeChild(textArea); // eslint-disable-next-line no-alert

      window.prompt(fallbackString, textToCopy);
      resolve();
    };

    try {
      const successful = document.execCommand('copy');

      if (!successful) {
        return magicCopyFailed('copy command unavailable');
      }

      document.body.removeChild(textArea);
      return resolve();
    } catch (err) {
      document.body.removeChild(textArea);
      return magicCopyFailed(err);
    }
  });
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/createSuperFocus.js":
/*!************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/createSuperFocus.js ***!
  \************************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ createSuperFocus)
/* harmony export */ });
/* harmony import */ var lodash_debounce__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! lodash.debounce */ "./src/jgDashboard/node_modules/lodash.debounce/index.js");
/* harmony import */ var _uppy_utils_lib_FOCUSABLE_ELEMENTS__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @uppy/utils/lib/FOCUSABLE_ELEMENTS */ "./src/jgDashboard/node_modules/@uppy/utils/lib/FOCUSABLE_ELEMENTS.js");
/* harmony import */ var _getActiveOverlayEl_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getActiveOverlayEl.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/getActiveOverlayEl.js");



/*
  Focuses on some element in the currently topmost overlay.

  1. If there are some [data-uppy-super-focusable] elements rendered already - focuses
     on the first superfocusable element, and leaves focus up to the control of
     a user (until currently focused element disappears from the screen [which
     can happen when overlay changes, or, e.g., when we click on a folder in googledrive]).
  2. If there are no [data-uppy-super-focusable] elements yet (or ever) - focuses
     on the first focusable element, but switches focus if superfocusable elements appear on next render.
*/

function createSuperFocus() {
  let lastFocusWasOnSuperFocusableEl = false;

  const superFocus = (dashboardEl, activeOverlayType) => {
    const overlayEl = (0,_getActiveOverlayEl_js__WEBPACK_IMPORTED_MODULE_2__["default"])(dashboardEl, activeOverlayType);
    const isFocusInOverlay = overlayEl.contains(document.activeElement); // If focus is already in the topmost overlay, AND on last update we focused on the superfocusable
    // element - then leave focus up to the user.
    // [Practical check] without this line, typing in the search input in googledrive overlay won't work.

    if (isFocusInOverlay && lastFocusWasOnSuperFocusableEl) return;
    const superFocusableEl = overlayEl.querySelector('[data-uppy-super-focusable]'); // If we are already in the topmost overlay, AND there are no super focusable elements yet, - leave focus up to the user.
    // [Practical check] without this line, if you are in an empty folder in google drive, and something's uploading in the
    // bg, - focus will be jumping to Done all the time.

    if (isFocusInOverlay && !superFocusableEl) return;

    if (superFocusableEl) {
      superFocusableEl.focus({
        preventScroll: true
      });
      lastFocusWasOnSuperFocusableEl = true;
    } else {
      const firstEl = overlayEl.querySelector(_uppy_utils_lib_FOCUSABLE_ELEMENTS__WEBPACK_IMPORTED_MODULE_1__["default"]);
      firstEl == null ? void 0 : firstEl.focus({
        preventScroll: true
      });
      lastFocusWasOnSuperFocusableEl = false;
    }
  }; // ___Why do we need to debounce?
  //    1. To deal with animations: overlay changes via animations, which results in the DOM updating AFTER plugin.update()
  //       already executed.
  //    [Practical check] without debounce, if we open the Url overlay, and click 'Done', Dashboard won't get focused again.
  //    [Practical check] if we delay 250ms instead of 260ms - IE11 won't get focused in same situation.
  //    2. Performance: there can be many state update()s in a second, and this function is called every time.


  return lodash_debounce__WEBPACK_IMPORTED_MODULE_0__(superFocus, 260);
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/getActiveOverlayEl.js":
/*!**************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/getActiveOverlayEl.js ***!
  \**************************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getActiveOverlayEl)
/* harmony export */ });
/**
 * @returns {HTMLElement} - either dashboard element, or the overlay that's most on top
 */
function getActiveOverlayEl(dashboardEl, activeOverlayType) {
  if (activeOverlayType) {
    const overlayEl = dashboardEl.querySelector(`[data-uppy-paneltype="${activeOverlayType}"]`); // if an overlay is already mounted

    if (overlayEl) return overlayEl;
  }

  return dashboardEl;
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/getFileTypeIcon.js":
/*!***********************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/getFileTypeIcon.js ***!
  \***********************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getIconByMime)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");


function iconImage() {
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    "aria-hidden": "true",
    focusable: "false",
    width: "25",
    height: "25",
    viewBox: "0 0 25 25"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("g", {
    fill: "#686DE0",
    fillRule: "evenodd"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    d: "M5 7v10h15V7H5zm0-1h15a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1z",
    fillRule: "nonzero"
  }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    d: "M6.35 17.172l4.994-5.026a.5.5 0 0 1 .707 0l2.16 2.16 3.505-3.505a.5.5 0 0 1 .707 0l2.336 2.31-.707.72-1.983-1.97-3.505 3.505a.5.5 0 0 1-.707 0l-2.16-2.159-3.938 3.939-1.409.026z",
    fillRule: "nonzero"
  }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("circle", {
    cx: "7.5",
    cy: "9.5",
    r: "1.5"
  })));
}

function iconAudio() {
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    "aria-hidden": "true",
    focusable: "false",
    className: "uppy-c-icon",
    width: "25",
    height: "25",
    viewBox: "0 0 25 25"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    d: "M9.5 18.64c0 1.14-1.145 2-2.5 2s-2.5-.86-2.5-2c0-1.14 1.145-2 2.5-2 .557 0 1.079.145 1.5.396V7.25a.5.5 0 0 1 .379-.485l9-2.25A.5.5 0 0 1 18.5 5v11.64c0 1.14-1.145 2-2.5 2s-2.5-.86-2.5-2c0-1.14 1.145-2 2.5-2 .557 0 1.079.145 1.5.396V8.67l-8 2v7.97zm8-11v-2l-8 2v2l8-2zM7 19.64c.855 0 1.5-.484 1.5-1s-.645-1-1.5-1-1.5.484-1.5 1 .645 1 1.5 1zm9-2c.855 0 1.5-.484 1.5-1s-.645-1-1.5-1-1.5.484-1.5 1 .645 1 1.5 1z",
    fill: "#049BCF",
    fillRule: "nonzero"
  }));
}

function iconVideo() {
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    "aria-hidden": "true",
    focusable: "false",
    className: "uppy-c-icon",
    width: "25",
    height: "25",
    viewBox: "0 0 25 25"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    d: "M16 11.834l4.486-2.691A1 1 0 0 1 22 10v6a1 1 0 0 1-1.514.857L16 14.167V17a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v2.834zM15 9H5v8h10V9zm1 4l5 3v-6l-5 3z",
    fill: "#19AF67",
    fillRule: "nonzero"
  }));
}

function iconPDF() {
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    "aria-hidden": "true",
    focusable: "false",
    className: "uppy-c-icon",
    width: "25",
    height: "25",
    viewBox: "0 0 25 25"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    d: "M9.766 8.295c-.691-1.843-.539-3.401.747-3.726 1.643-.414 2.505.938 2.39 3.299-.039.79-.194 1.662-.537 3.148.324.49.66.967 1.055 1.51.17.231.382.488.629.757 1.866-.128 3.653.114 4.918.655 1.487.635 2.192 1.685 1.614 2.84-.566 1.133-1.839 1.084-3.416.249-1.141-.604-2.457-1.634-3.51-2.707a13.467 13.467 0 0 0-2.238.426c-1.392 4.051-4.534 6.453-5.707 4.572-.986-1.58 1.38-4.206 4.914-5.375.097-.322.185-.656.264-1.001.08-.353.306-1.31.407-1.737-.678-1.059-1.2-2.031-1.53-2.91zm2.098 4.87c-.033.144-.068.287-.104.427l.033-.01-.012.038a14.065 14.065 0 0 1 1.02-.197l-.032-.033.052-.004a7.902 7.902 0 0 1-.208-.271c-.197-.27-.38-.526-.555-.775l-.006.028-.002-.003c-.076.323-.148.632-.186.8zm5.77 2.978c1.143.605 1.832.632 2.054.187.26-.519-.087-1.034-1.113-1.473-.911-.39-2.175-.608-3.55-.608.845.766 1.787 1.459 2.609 1.894zM6.559 18.789c.14.223.693.16 1.425-.413.827-.648 1.61-1.747 2.208-3.206-2.563 1.064-4.102 2.867-3.633 3.62zm5.345-10.97c.088-1.793-.351-2.48-1.146-2.28-.473.119-.564 1.05-.056 2.405.213.566.52 1.188.908 1.859.18-.858.268-1.453.294-1.984z",
    fill: "#E2514A",
    fillRule: "nonzero"
  }));
}

function iconArchive() {
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    "aria-hidden": "true",
    focusable: "false",
    width: "25",
    height: "25",
    viewBox: "0 0 25 25"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    d: "M10.45 2.05h1.05a.5.5 0 0 1 .5.5v.024a.5.5 0 0 1-.5.5h-1.05a.5.5 0 0 1-.5-.5V2.55a.5.5 0 0 1 .5-.5zm2.05 1.024h1.05a.5.5 0 0 1 .5.5V3.6a.5.5 0 0 1-.5.5H12.5a.5.5 0 0 1-.5-.5v-.025a.5.5 0 0 1 .5-.5v-.001zM10.45 0h1.05a.5.5 0 0 1 .5.5v.025a.5.5 0 0 1-.5.5h-1.05a.5.5 0 0 1-.5-.5V.5a.5.5 0 0 1 .5-.5zm2.05 1.025h1.05a.5.5 0 0 1 .5.5v.024a.5.5 0 0 1-.5.5H12.5a.5.5 0 0 1-.5-.5v-.024a.5.5 0 0 1 .5-.5zm-2.05 3.074h1.05a.5.5 0 0 1 .5.5v.025a.5.5 0 0 1-.5.5h-1.05a.5.5 0 0 1-.5-.5v-.025a.5.5 0 0 1 .5-.5zm2.05 1.025h1.05a.5.5 0 0 1 .5.5v.024a.5.5 0 0 1-.5.5H12.5a.5.5 0 0 1-.5-.5v-.024a.5.5 0 0 1 .5-.5zm-2.05 1.024h1.05a.5.5 0 0 1 .5.5v.025a.5.5 0 0 1-.5.5h-1.05a.5.5 0 0 1-.5-.5v-.025a.5.5 0 0 1 .5-.5zm2.05 1.025h1.05a.5.5 0 0 1 .5.5v.025a.5.5 0 0 1-.5.5H12.5a.5.5 0 0 1-.5-.5v-.025a.5.5 0 0 1 .5-.5zm-2.05 1.025h1.05a.5.5 0 0 1 .5.5v.025a.5.5 0 0 1-.5.5h-1.05a.5.5 0 0 1-.5-.5v-.025a.5.5 0 0 1 .5-.5zm2.05 1.025h1.05a.5.5 0 0 1 .5.5v.024a.5.5 0 0 1-.5.5H12.5a.5.5 0 0 1-.5-.5v-.024a.5.5 0 0 1 .5-.5zm-1.656 3.074l-.82 5.946c.52.302 1.174.458 1.976.458.803 0 1.455-.156 1.975-.458l-.82-5.946h-2.311zm0-1.025h2.312c.512 0 .946.378 1.015.885l.82 5.946c.056.412-.142.817-.501 1.026-.686.398-1.515.597-2.49.597-.974 0-1.804-.199-2.49-.597a1.025 1.025 0 0 1-.5-1.026l.819-5.946c.07-.507.503-.885 1.015-.885zm.545 6.6a.5.5 0 0 1-.397-.561l.143-.999a.5.5 0 0 1 .495-.429h.74a.5.5 0 0 1 .495.43l.143.998a.5.5 0 0 1-.397.561c-.404.08-.819.08-1.222 0z",
    fill: "#00C469",
    fillRule: "nonzero"
  }));
}

function iconFile() {
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    "aria-hidden": "true",
    focusable: "false",
    className: "uppy-c-icon",
    width: "25",
    height: "25",
    viewBox: "0 0 25 25"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("g", {
    fill: "#A7AFB7",
    fillRule: "nonzero"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    d: "M5.5 22a.5.5 0 0 1-.5-.5v-18a.5.5 0 0 1 .5-.5h10.719a.5.5 0 0 1 .367.16l3.281 3.556a.5.5 0 0 1 .133.339V21.5a.5.5 0 0 1-.5.5h-14zm.5-1h13V7.25L16 4H6v17z"
  }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    d: "M15 4v3a1 1 0 0 0 1 1h3V7h-3V4h-1z"
  })));
}

function iconText() {
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    "aria-hidden": "true",
    focusable: "false",
    className: "uppy-c-icon",
    width: "25",
    height: "25",
    viewBox: "0 0 25 25"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    d: "M4.5 7h13a.5.5 0 1 1 0 1h-13a.5.5 0 0 1 0-1zm0 3h15a.5.5 0 1 1 0 1h-15a.5.5 0 1 1 0-1zm0 3h15a.5.5 0 1 1 0 1h-15a.5.5 0 1 1 0-1zm0 3h10a.5.5 0 1 1 0 1h-10a.5.5 0 1 1 0-1z",
    fill: "#5A5E69",
    fillRule: "nonzero"
  }));
}

function getIconByMime(fileType) {
  const defaultChoice = {
    color: '#838999',
    icon: iconFile()
  };
  if (!fileType) return defaultChoice;
  const fileTypeGeneral = fileType.split('/')[0];
  const fileTypeSpecific = fileType.split('/')[1]; // Text

  if (fileTypeGeneral === 'text') {
    return {
      color: '#5a5e69',
      icon: iconText()
    };
  } // Image


  if (fileTypeGeneral === 'image') {
    return {
      color: '#686de0',
      icon: iconImage()
    };
  } // Audio


  if (fileTypeGeneral === 'audio') {
    return {
      color: '#068dbb',
      icon: iconAudio()
    };
  } // Video


  if (fileTypeGeneral === 'video') {
    return {
      color: '#19af67',
      icon: iconVideo()
    };
  } // PDF


  if (fileTypeGeneral === 'application' && fileTypeSpecific === 'pdf') {
    return {
      color: '#e25149',
      icon: iconPDF()
    };
  } // Archive


  const archiveTypes = ['zip', 'x-7z-compressed', 'x-rar-compressed', 'x-tar', 'x-gzip', 'x-apple-diskimage'];

  if (fileTypeGeneral === 'application' && archiveTypes.indexOf(fileTypeSpecific) !== -1) {
    return {
      color: '#00C469',
      icon: iconArchive()
    };
  }

  return defaultChoice;
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/ignoreEvent.js":
/*!*******************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/ignoreEvent.js ***!
  \*******************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
// ignore drop/paste events if they are not in input or textarea —
// otherwise when Url plugin adds drop/paste listeners to this.el,
// draging UI elements or pasting anything into any field triggers those events —
// Url treats them as URLs that need to be imported
function ignoreEvent(ev) {
  const {
    tagName
  } = ev.target;

  if (tagName === 'INPUT' || tagName === 'TEXTAREA') {
    ev.stopPropagation();
    return;
  }

  ev.preventDefault();
  ev.stopPropagation();
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ignoreEvent);

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/trapFocus.js":
/*!*****************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/trapFocus.js ***!
  \*****************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   forInline: () => (/* binding */ forInline),
/* harmony export */   forModal: () => (/* binding */ trapFocus)
/* harmony export */ });
/* harmony import */ var _uppy_utils_lib_toArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @uppy/utils/lib/toArray */ "./src/jgDashboard/node_modules/@uppy/utils/lib/toArray.js");
/* harmony import */ var _uppy_utils_lib_FOCUSABLE_ELEMENTS__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @uppy/utils/lib/FOCUSABLE_ELEMENTS */ "./src/jgDashboard/node_modules/@uppy/utils/lib/FOCUSABLE_ELEMENTS.js");
/* harmony import */ var _getActiveOverlayEl_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getActiveOverlayEl.js */ "./src/jgDashboard/node_modules/@uppy/dashboard/lib/utils/getActiveOverlayEl.js");




function focusOnFirstNode(event, nodes) {
  const node = nodes[0];

  if (node) {
    node.focus();
    event.preventDefault();
  }
}

function focusOnLastNode(event, nodes) {
  const node = nodes[nodes.length - 1];

  if (node) {
    node.focus();
    event.preventDefault();
  }
} // ___Why not just use (focusedItemIndex === -1)?
//    Firefox thinks <ul> is focusable, but we don't have <ul>s in our FOCUSABLE_ELEMENTS. Which means that if we tab into
//    the <ul>, code will think that we are not in the active overlay, and we should focusOnFirstNode() of the currently
//    active overlay!
//    [Practical check] if we use (focusedItemIndex === -1), instagram provider in firefox will never get focus on its pics
//    in the <ul>.


function isFocusInOverlay(activeOverlayEl) {
  return activeOverlayEl.contains(document.activeElement);
}

function trapFocus(event, activeOverlayType, dashboardEl) {
  const activeOverlayEl = (0,_getActiveOverlayEl_js__WEBPACK_IMPORTED_MODULE_2__["default"])(dashboardEl, activeOverlayType);
  const focusableNodes = (0,_uppy_utils_lib_toArray__WEBPACK_IMPORTED_MODULE_0__["default"])(activeOverlayEl.querySelectorAll(_uppy_utils_lib_FOCUSABLE_ELEMENTS__WEBPACK_IMPORTED_MODULE_1__["default"]));
  const focusedItemIndex = focusableNodes.indexOf(document.activeElement); // If we pressed tab, and focus is not yet within the current overlay - focus on
  // the first element within the current overlay.
  // This is a safety measure (for when user returns from another tab e.g.), most
  // plugins will try to focus on some important element as it loads.

  if (!isFocusInOverlay(activeOverlayEl)) {
    focusOnFirstNode(event, focusableNodes); // If we pressed shift + tab, and we're on the first element of a modal
  } else if (event.shiftKey && focusedItemIndex === 0) {
    focusOnLastNode(event, focusableNodes); // If we pressed tab, and we're on the last element of the modal
  } else if (!event.shiftKey && focusedItemIndex === focusableNodes.length - 1) {
    focusOnFirstNode(event, focusableNodes);
  }
} // Traps focus inside of the currently open overlay (e.g. Dashboard, or e.g. Instagram),
// never lets focus disappear from the modal.


 // Traps focus inside of the currently open overlay, unless overlay is null - then let the user tab away.

function forInline(event, activeOverlayType, dashboardEl) {
  // ___When we're in the bare 'Drop files here, paste, browse or import from' screen
  if (activeOverlayType === null) {// Do nothing and let the browser handle it, user can tab away from Uppy to other elements on the page
    // ___When there is some overlay with 'Done' button
  } else {
    // Trap the focus inside this overlay!
    // User can close the overlay (click 'Done') if they want to travel away from Uppy.
    trapFocus(event, activeOverlayType, dashboardEl);
  }
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/informer/lib/FadeIn.js":
/*!*******************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/informer/lib/FadeIn.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ FadeIn)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");

const TRANSITION_MS = 300;
class FadeIn extends preact__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.ref = (0,preact__WEBPACK_IMPORTED_MODULE_0__.createRef)();
  }

  componentWillEnter(callback) {
    this.ref.current.style.opacity = '1';
    this.ref.current.style.transform = 'none';
    setTimeout(callback, TRANSITION_MS);
  }

  componentWillLeave(callback) {
    this.ref.current.style.opacity = '0';
    this.ref.current.style.transform = 'translateY(350%)';
    setTimeout(callback, TRANSITION_MS);
  }

  render() {
    const {
      children
    } = this.props;
    return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
      className: "uppy-Informer-animated",
      ref: this.ref
    }, children);
  }

}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/informer/lib/Informer.js":
/*!*********************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/informer/lib/Informer.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Informer)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var _uppy_core__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @uppy/core */ "./src/jgDashboard/node_modules/@uppy/core/lib/index.js");
/* harmony import */ var _FadeIn_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FadeIn.js */ "./src/jgDashboard/node_modules/@uppy/informer/lib/FadeIn.js");
/* harmony import */ var _TransitionGroup_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./TransitionGroup.js */ "./src/jgDashboard/node_modules/@uppy/informer/lib/TransitionGroup.js");
/* eslint-disable jsx-a11y/no-noninteractive-element-interactions  */

/* eslint-disable jsx-a11y/click-events-have-key-events */




const packageJson = {
  "version": "3.0.2"
};
/**
 * Informer
 * Shows rad message bubbles
 * used like this: `uppy.info('hello world', 'info', 5000)`
 * or for errors: `uppy.info('Error uploading img.jpg', 'error', 5000)`
 *
 */

class Informer extends _uppy_core__WEBPACK_IMPORTED_MODULE_1__.UIPlugin {
  constructor(uppy, opts) {
    super(uppy, opts);

    this.render = state => {
      return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
        className: "uppy uppy-Informer"
      }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_TransitionGroup_js__WEBPACK_IMPORTED_MODULE_3__["default"], null, state.info.map(info => (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_FadeIn_js__WEBPACK_IMPORTED_MODULE_2__["default"], {
        key: info.message
      }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("p", {
        role: "alert"
      }, info.message, ' ', info.details && (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("span", {
        "aria-label": info.details,
        "data-microtip-position": "top-left",
        "data-microtip-size": "medium",
        role: "tooltip" // eslint-disable-next-line no-alert
        ,
        onClick: () => alert(`${info.message} \n\n ${info.details}`)
      }, "?"))))));
    };

    this.type = 'progressindicator';
    this.id = this.opts.id || 'Informer';
    this.title = 'Informer'; // set default options

    const defaultOptions = {}; // merge default options with the ones set by user

    this.opts = { ...defaultOptions,
      ...opts
    };
  }

  install() {
    const {
      target
    } = this.opts;

    if (target) {
      this.mount(target, this);
    }
  }

}
Informer.VERSION = packageJson.version;

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/informer/lib/TransitionGroup.js":
/*!****************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/informer/lib/TransitionGroup.js ***!
  \****************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* eslint-disable */

/**
 * @source https://github.com/developit/preact-transition-group
 */


function assign(obj, props) {
  return Object.assign(obj, props);
}

function getKey(vnode, fallback) {
  var _vnode$key;

  return (_vnode$key = vnode == null ? void 0 : vnode.key) != null ? _vnode$key : fallback;
}

function linkRef(component, name) {
  const cache = component._ptgLinkedRefs || (component._ptgLinkedRefs = {});
  return cache[name] || (cache[name] = c => {
    component.refs[name] = c;
  });
}

function getChildMapping(children) {
  const out = {};

  for (let i = 0; i < children.length; i++) {
    if (children[i] != null) {
      const key = getKey(children[i], i.toString(36));
      out[key] = children[i];
    }
  }

  return out;
}

function mergeChildMappings(prev, next) {
  prev = prev || {};
  next = next || {};

  const getValueForKey = key => next.hasOwnProperty(key) ? next[key] : prev[key]; // For each key of `next`, the list of keys to insert before that key in
  // the combined list


  const nextKeysPending = {};
  let pendingKeys = [];

  for (const prevKey in prev) {
    if (next.hasOwnProperty(prevKey)) {
      if (pendingKeys.length) {
        nextKeysPending[prevKey] = pendingKeys;
        pendingKeys = [];
      }
    } else {
      pendingKeys.push(prevKey);
    }
  }

  const childMapping = {};

  for (const nextKey in next) {
    if (nextKeysPending.hasOwnProperty(nextKey)) {
      for (let i = 0; i < nextKeysPending[nextKey].length; i++) {
        const pendingNextKey = nextKeysPending[nextKey][i];
        childMapping[nextKeysPending[nextKey][i]] = getValueForKey(pendingNextKey);
      }
    }

    childMapping[nextKey] = getValueForKey(nextKey);
  } // Finally, add the keys which didn't appear before any key in `next`


  for (let i = 0; i < pendingKeys.length; i++) {
    childMapping[pendingKeys[i]] = getValueForKey(pendingKeys[i]);
  }

  return childMapping;
}

const identity = i => i;

class TransitionGroup extends preact__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor(props, context) {
    super(props, context);
    this.refs = {};
    this.state = {
      children: getChildMapping((0,preact__WEBPACK_IMPORTED_MODULE_0__.toChildArray)((0,preact__WEBPACK_IMPORTED_MODULE_0__.toChildArray)(this.props.children)) || [])
    };
    this.performAppear = this.performAppear.bind(this);
    this.performEnter = this.performEnter.bind(this);
    this.performLeave = this.performLeave.bind(this);
  }

  componentWillMount() {
    this.currentlyTransitioningKeys = {};
    this.keysToAbortLeave = [];
    this.keysToEnter = [];
    this.keysToLeave = [];
  }

  componentDidMount() {
    const initialChildMapping = this.state.children;

    for (const key in initialChildMapping) {
      if (initialChildMapping[key]) {
        // this.performAppear(getKey(initialChildMapping[key], key));
        this.performAppear(key);
      }
    }
  }

  componentWillReceiveProps(nextProps) {
    const nextChildMapping = getChildMapping((0,preact__WEBPACK_IMPORTED_MODULE_0__.toChildArray)(nextProps.children) || []);
    const prevChildMapping = this.state.children;
    this.setState(prevState => ({
      children: mergeChildMappings(prevState.children, nextChildMapping)
    }));
    let key;

    for (key in nextChildMapping) {
      if (nextChildMapping.hasOwnProperty(key)) {
        const hasPrev = prevChildMapping && prevChildMapping.hasOwnProperty(key); // We should re-enter the component and abort its leave function

        if (nextChildMapping[key] && hasPrev && this.currentlyTransitioningKeys[key]) {
          this.keysToEnter.push(key);
          this.keysToAbortLeave.push(key);
        } else if (nextChildMapping[key] && !hasPrev && !this.currentlyTransitioningKeys[key]) {
          this.keysToEnter.push(key);
        }
      }
    }

    for (key in prevChildMapping) {
      if (prevChildMapping.hasOwnProperty(key)) {
        const hasNext = nextChildMapping && nextChildMapping.hasOwnProperty(key);

        if (prevChildMapping[key] && !hasNext && !this.currentlyTransitioningKeys[key]) {
          this.keysToLeave.push(key);
        }
      }
    }
  }

  componentDidUpdate() {
    const {
      keysToEnter
    } = this;
    this.keysToEnter = [];
    keysToEnter.forEach(this.performEnter);
    const {
      keysToLeave
    } = this;
    this.keysToLeave = [];
    keysToLeave.forEach(this.performLeave);
  }

  _finishAbort(key) {
    const idx = this.keysToAbortLeave.indexOf(key);

    if (idx !== -1) {
      this.keysToAbortLeave.splice(idx, 1);
    }
  }

  performAppear(key) {
    this.currentlyTransitioningKeys[key] = true;
    const component = this.refs[key];

    if (component != null && component.componentWillAppear) {
      component.componentWillAppear(this._handleDoneAppearing.bind(this, key));
    } else {
      this._handleDoneAppearing(key);
    }
  }

  _handleDoneAppearing(key) {
    const component = this.refs[key];

    if (component != null && component.componentDidAppear) {
      component.componentDidAppear();
    }

    delete this.currentlyTransitioningKeys[key];

    this._finishAbort(key);

    const currentChildMapping = getChildMapping((0,preact__WEBPACK_IMPORTED_MODULE_0__.toChildArray)(this.props.children) || []);

    if (!currentChildMapping || !currentChildMapping.hasOwnProperty(key)) {
      // This was removed before it had fully appeared. Remove it.
      this.performLeave(key);
    }
  }

  performEnter(key) {
    this.currentlyTransitioningKeys[key] = true;
    const component = this.refs[key];

    if (component != null && component.componentWillEnter) {
      component.componentWillEnter(this._handleDoneEntering.bind(this, key));
    } else {
      this._handleDoneEntering(key);
    }
  }

  _handleDoneEntering(key) {
    const component = this.refs[key];

    if (component != null && component.componentDidEnter) {
      component.componentDidEnter();
    }

    delete this.currentlyTransitioningKeys[key];

    this._finishAbort(key);

    const currentChildMapping = getChildMapping((0,preact__WEBPACK_IMPORTED_MODULE_0__.toChildArray)(this.props.children) || []);

    if (!currentChildMapping || !currentChildMapping.hasOwnProperty(key)) {
      // This was removed before it had fully entered. Remove it.
      this.performLeave(key);
    }
  }

  performLeave(key) {
    // If we should immediately abort this leave function,
    // don't run the leave transition at all.
    const idx = this.keysToAbortLeave.indexOf(key);

    if (idx !== -1) {
      return;
    }

    this.currentlyTransitioningKeys[key] = true;
    const component = this.refs[key];

    if (component != null && component.componentWillLeave) {
      component.componentWillLeave(this._handleDoneLeaving.bind(this, key));
    } else {
      // Note that this is somewhat dangerous b/c it calls setState()
      // again, effectively mutating the component before all the work
      // is done.
      this._handleDoneLeaving(key);
    }
  }

  _handleDoneLeaving(key) {
    // If we should immediately abort the leave,
    // then skip this altogether
    const idx = this.keysToAbortLeave.indexOf(key);

    if (idx !== -1) {
      return;
    }

    const component = this.refs[key];

    if (component != null && component.componentDidLeave) {
      component.componentDidLeave();
    }

    delete this.currentlyTransitioningKeys[key];
    const currentChildMapping = getChildMapping((0,preact__WEBPACK_IMPORTED_MODULE_0__.toChildArray)(this.props.children) || []);

    if (currentChildMapping && currentChildMapping.hasOwnProperty(key)) {
      // This entered again before it fully left. Add it again.
      this.performEnter(key);
    } else {
      const children = assign({}, this.state.children);
      delete children[key];
      this.setState({
        children
      });
    }
  }

  render(_ref, _ref2) {
    let {
      childFactory,
      transitionLeave,
      transitionName,
      transitionAppear,
      transitionEnter,
      transitionLeaveTimeout,
      transitionEnterTimeout,
      transitionAppearTimeout,
      component,
      ...props
    } = _ref;
    let {
      children
    } = _ref2;
    // TODO: we could get rid of the need for the wrapper node
    // by cloning a single child
    const childrenToRender = Object.entries(children).map(_ref3 => {
      let [key, child] = _ref3;
      if (!child) return undefined;
      const ref = linkRef(this, key);
      return (0,preact__WEBPACK_IMPORTED_MODULE_0__.cloneElement)(childFactory(child), {
        ref,
        key
      });
    }).filter(Boolean);
    return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(component, props, childrenToRender);
  }

}

TransitionGroup.defaultProps = {
  component: 'span',
  childFactory: identity
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (TransitionGroup);

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/informer/lib/index.js":
/*!******************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/informer/lib/index.js ***!
  \******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _Informer_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _Informer_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Informer.js */ "./src/jgDashboard/node_modules/@uppy/informer/lib/Informer.js");


/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/status-bar/lib/Components.js":
/*!*************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/status-bar/lib/Components.js ***!
  \*************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   CancelBtn: () => (/* binding */ CancelBtn),
/* harmony export */   DoneBtn: () => (/* binding */ DoneBtn),
/* harmony export */   LoadingSpinner: () => (/* binding */ LoadingSpinner),
/* harmony export */   PauseResumeButton: () => (/* binding */ PauseResumeButton),
/* harmony export */   ProgressBarComplete: () => (/* binding */ ProgressBarComplete),
/* harmony export */   ProgressBarError: () => (/* binding */ ProgressBarError),
/* harmony export */   ProgressBarProcessing: () => (/* binding */ ProgressBarProcessing),
/* harmony export */   ProgressBarUploading: () => (/* binding */ ProgressBarUploading),
/* harmony export */   ProgressDetails: () => (/* binding */ ProgressDetails),
/* harmony export */   RetryBtn: () => (/* binding */ RetryBtn),
/* harmony export */   UploadBtn: () => (/* binding */ UploadBtn)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! classnames */ "./src/jgDashboard/node_modules/classnames/index.js");
/* harmony import */ var lodash_throttle__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! lodash.throttle */ "./src/jgDashboard/node_modules/lodash.throttle/index.js");
/* harmony import */ var _transloadit_prettier_bytes__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @transloadit/prettier-bytes */ "./src/jgDashboard/node_modules/@uppy/status-bar/node_modules/@transloadit/prettier-bytes/prettierBytes.js");
/* harmony import */ var _uppy_utils_lib_prettyETA__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @uppy/utils/lib/prettyETA */ "./src/jgDashboard/node_modules/@uppy/utils/lib/prettyETA.js");
/* harmony import */ var _StatusBarStates_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./StatusBarStates.js */ "./src/jgDashboard/node_modules/@uppy/status-bar/lib/StatusBarStates.js");






const DOT = `\u00B7`;

const renderDot = () => ` ${DOT} `;

function UploadBtn(props) {
  const {
    newFiles,
    isUploadStarted,
    recoveredState,
    i18n,
    uploadState,
    isSomeGhost,
    startUpload
  } = props;
  const uploadBtnClassNames = classnames__WEBPACK_IMPORTED_MODULE_1__('uppy-u-reset', 'uppy-c-btn', 'uppy-StatusBar-actionBtn', 'uppy-StatusBar-actionBtn--upload', {
    'uppy-c-btn-primary': uploadState === _StatusBarStates_js__WEBPACK_IMPORTED_MODULE_5__["default"].STATE_WAITING
  }, {
    'uppy-StatusBar-actionBtn--disabled': isSomeGhost
  });
  const uploadBtnText = newFiles && isUploadStarted && !recoveredState ? i18n('uploadXNewFiles', {
    smart_count: newFiles
  }) : i18n('uploadXFiles', {
    smart_count: newFiles
  });
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    type: "button",
    className: uploadBtnClassNames,
    "aria-label": i18n('uploadXFiles', {
      smart_count: newFiles
    }),
    onClick: startUpload,
    disabled: isSomeGhost,
    "data-uppy-super-focusable": true
  }, uploadBtnText);
}

function RetryBtn(props) {
  const {
    i18n,
    uppy
  } = props;
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    type: "button",
    className: "uppy-u-reset uppy-c-btn uppy-StatusBar-actionBtn uppy-StatusBar-actionBtn--retry",
    "aria-label": i18n('retryUpload'),
    onClick: () => uppy.retryAll().catch(() => {
      /* Error reported and handled via an event */
    }),
    "data-uppy-super-focusable": true
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    "aria-hidden": "true",
    focusable: "false",
    className: "uppy-c-icon",
    width: "8",
    height: "10",
    viewBox: "0 0 8 10"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    d: "M4 2.408a2.75 2.75 0 1 0 2.75 2.75.626.626 0 0 1 1.25.018v.023a4 4 0 1 1-4-4.041V.25a.25.25 0 0 1 .389-.208l2.299 1.533a.25.25 0 0 1 0 .416l-2.3 1.533A.25.25 0 0 1 4 3.316v-.908z"
  })), i18n('retry'));
}

function CancelBtn(props) {
  const {
    i18n,
    uppy
  } = props;
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    type: "button",
    className: "uppy-u-reset uppy-StatusBar-actionCircleBtn",
    title: i18n('cancel'),
    "aria-label": i18n('cancel'),
    onClick: () => uppy.cancelAll(),
    "data-cy": "cancel",
    "data-uppy-super-focusable": true
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    "aria-hidden": "true",
    focusable: "false",
    className: "uppy-c-icon",
    width: "16",
    height: "16",
    viewBox: "0 0 16 16"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("g", {
    fill: "none",
    fillRule: "evenodd"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("circle", {
    fill: "#888",
    cx: "8",
    cy: "8",
    r: "8"
  }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    fill: "#FFF",
    d: "M9.283 8l2.567 2.567-1.283 1.283L8 9.283 5.433 11.85 4.15 10.567 6.717 8 4.15 5.433 5.433 4.15 8 6.717l2.567-2.567 1.283 1.283z"
  }))));
}

function PauseResumeButton(props) {
  const {
    isAllPaused,
    i18n,
    isAllComplete,
    resumableUploads,
    uppy
  } = props;
  const title = isAllPaused ? i18n('resume') : i18n('pause');

  function togglePauseResume() {
    if (isAllComplete) return null;

    if (!resumableUploads) {
      return uppy.cancelAll();
    }

    if (isAllPaused) {
      return uppy.resumeAll();
    }

    return uppy.pauseAll();
  }

  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    title: title,
    "aria-label": title,
    className: "uppy-u-reset uppy-StatusBar-actionCircleBtn",
    type: "button",
    onClick: togglePauseResume,
    "data-uppy-super-focusable": true
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    "aria-hidden": "true",
    focusable: "false",
    className: "uppy-c-icon",
    width: "16",
    height: "16",
    viewBox: "0 0 16 16"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("g", {
    fill: "none",
    fillRule: "evenodd"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("circle", {
    fill: "#888",
    cx: "8",
    cy: "8",
    r: "8"
  }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    fill: "#FFF",
    d: isAllPaused ? 'M6 4.25L11.5 8 6 11.75z' : 'M5 4.5h2v7H5v-7zm4 0h2v7H9v-7z'
  }))));
}

function DoneBtn(props) {
  const {
    i18n,
    doneButtonHandler
  } = props;
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    type: "button",
    className: "uppy-u-reset uppy-c-btn uppy-StatusBar-actionBtn uppy-StatusBar-actionBtn--done",
    onClick: doneButtonHandler,
    "data-uppy-super-focusable": true
  }, i18n('done'));
}

function LoadingSpinner() {
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    className: "uppy-StatusBar-spinner",
    "aria-hidden": "true",
    focusable: "false",
    width: "14",
    height: "14"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    d: "M13.983 6.547c-.12-2.509-1.64-4.893-3.939-5.936-2.48-1.127-5.488-.656-7.556 1.094C.524 3.367-.398 6.048.162 8.562c.556 2.495 2.46 4.52 4.94 5.183 2.932.784 5.61-.602 7.256-3.015-1.493 1.993-3.745 3.309-6.298 2.868-2.514-.434-4.578-2.349-5.153-4.84a6.226 6.226 0 0 1 2.98-6.778C6.34.586 9.74 1.1 11.373 3.493c.407.596.693 1.282.842 1.988.127.598.073 1.197.161 1.794.078.525.543 1.257 1.15.864.525-.341.49-1.05.456-1.592-.007-.15.02.3 0 0",
    fillRule: "evenodd"
  }));
}

function ProgressBarProcessing(props) {
  const {
    progress
  } = props;
  const {
    value,
    mode,
    message
  } = progress;
  const roundedValue = Math.round(value * 100);
  const dot = `\u00B7`;
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-StatusBar-content"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(LoadingSpinner, null), mode === 'determinate' ? `${roundedValue}% ${dot} ` : '', message);
}

function ProgressDetails(props) {
  const {
    numUploads,
    complete,
    totalUploadedSize,
    totalSize,
    totalETA,
    i18n
  } = props;
  const ifShowFilesUploadedOfTotal = numUploads > 1;
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-StatusBar-statusSecondary"
  }, ifShowFilesUploadedOfTotal && i18n('filesUploadedOfTotal', {
    complete,
    smart_count: numUploads
  }), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("span", {
    className: "uppy-StatusBar-additionalInfo"
  }, ifShowFilesUploadedOfTotal && renderDot(), i18n('dataUploadedOfTotal', {
    complete: _transloadit_prettier_bytes__WEBPACK_IMPORTED_MODULE_3__(totalUploadedSize),
    total: _transloadit_prettier_bytes__WEBPACK_IMPORTED_MODULE_3__(totalSize)
  }), renderDot(), i18n('xTimeLeft', {
    time: (0,_uppy_utils_lib_prettyETA__WEBPACK_IMPORTED_MODULE_4__["default"])(totalETA)
  })));
}

function FileUploadCount(props) {
  const {
    i18n,
    complete,
    numUploads
  } = props;
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-StatusBar-statusSecondary"
  }, i18n('filesUploadedOfTotal', {
    complete,
    smart_count: numUploads
  }));
}

function UploadNewlyAddedFiles(props) {
  const {
    i18n,
    newFiles,
    startUpload
  } = props;
  const uploadBtnClassNames = classnames__WEBPACK_IMPORTED_MODULE_1__('uppy-u-reset', 'uppy-c-btn', 'uppy-StatusBar-actionBtn', 'uppy-StatusBar-actionBtn--uploadNewlyAdded');
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-StatusBar-statusSecondary"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-StatusBar-statusSecondaryHint"
  }, i18n('xMoreFilesAdded', {
    smart_count: newFiles
  })), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    type: "button",
    className: uploadBtnClassNames,
    "aria-label": i18n('uploadXFiles', {
      smart_count: newFiles
    }),
    onClick: startUpload
  }, i18n('upload')));
}

const ThrottledProgressDetails = lodash_throttle__WEBPACK_IMPORTED_MODULE_2__(ProgressDetails, 500, {
  leading: true,
  trailing: true
});

function ProgressBarUploading(props) {
  const {
    i18n,
    supportsUploadProgress,
    totalProgress,
    showProgressDetails,
    isUploadStarted,
    isAllComplete,
    isAllPaused,
    newFiles,
    numUploads,
    complete,
    totalUploadedSize,
    totalSize,
    totalETA,
    startUpload
  } = props;
  const showUploadNewlyAddedFiles = newFiles && isUploadStarted;

  if (!isUploadStarted || isAllComplete) {
    return null;
  }

  const title = isAllPaused ? i18n('paused') : i18n('uploading');

  function renderProgressDetails() {
    if (!isAllPaused && !showUploadNewlyAddedFiles && showProgressDetails) {
      if (supportsUploadProgress) {
        return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(ThrottledProgressDetails, {
          numUploads: numUploads,
          complete: complete,
          totalUploadedSize: totalUploadedSize,
          totalSize: totalSize,
          totalETA: totalETA,
          i18n: i18n
        });
      }

      return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(FileUploadCount, {
        i18n: i18n,
        complete: complete,
        numUploads: numUploads
      });
    }

    return null;
  }

  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-StatusBar-content",
    "aria-label": title,
    title: title
  }, !isAllPaused ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(LoadingSpinner, null) : null, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-StatusBar-status"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-StatusBar-statusPrimary"
  }, supportsUploadProgress ? `${title}: ${totalProgress}%` : title), renderProgressDetails(), showUploadNewlyAddedFiles ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(UploadNewlyAddedFiles, {
    i18n: i18n,
    newFiles: newFiles,
    startUpload: startUpload
  }) : null));
}

function ProgressBarComplete(props) {
  const {
    i18n
  } = props;
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-StatusBar-content",
    role: "status",
    title: i18n('complete')
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-StatusBar-status"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-StatusBar-statusPrimary"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    "aria-hidden": "true",
    focusable: "false",
    className: "uppy-StatusBar-statusIndicator uppy-c-icon",
    width: "15",
    height: "11",
    viewBox: "0 0 15 11"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    d: "M.414 5.843L1.627 4.63l3.472 3.472L13.202 0l1.212 1.213L5.1 10.528z"
  })), i18n('complete'))));
}

function ProgressBarError(props) {
  const {
    error,
    i18n,
    complete,
    numUploads
  } = props;

  function displayErrorAlert() {
    const errorMessage = `${i18n('uploadFailed')} \n\n ${error}`; // eslint-disable-next-line no-alert

    alert(errorMessage); // TODO: move to custom alert implementation
  }

  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-StatusBar-content",
    title: i18n('uploadFailed')
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("svg", {
    "aria-hidden": "true",
    focusable: "false",
    className: "uppy-StatusBar-statusIndicator uppy-c-icon",
    width: "11",
    height: "11",
    viewBox: "0 0 11 11"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("path", {
    d: "M4.278 5.5L0 1.222 1.222 0 5.5 4.278 9.778 0 11 1.222 6.722 5.5 11 9.778 9.778 11 5.5 6.722 1.222 11 0 9.778z"
  })), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-StatusBar-status"
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-StatusBar-statusPrimary"
  }, i18n('uploadFailed'), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("button", {
    className: "uppy-u-reset uppy-StatusBar-details",
    "aria-label": i18n('showErrorDetails'),
    "data-microtip-position": "top-right",
    "data-microtip-size": "medium",
    onClick: displayErrorAlert,
    type: "button"
  }, "?")), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(FileUploadCount, {
    i18n: i18n,
    complete: complete,
    numUploads: numUploads
  })));
}



/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/status-bar/lib/StatusBar.js":
/*!************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/status-bar/lib/StatusBar.js ***!
  \************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ StatusBar)
/* harmony export */ });
/* harmony import */ var _uppy_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @uppy/core */ "./src/jgDashboard/node_modules/@uppy/core/lib/index.js");
/* harmony import */ var _uppy_utils_lib_getSpeed__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @uppy/utils/lib/getSpeed */ "./src/jgDashboard/node_modules/@uppy/utils/lib/getSpeed.js");
/* harmony import */ var _uppy_utils_lib_getBytesRemaining__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @uppy/utils/lib/getBytesRemaining */ "./src/jgDashboard/node_modules/@uppy/utils/lib/getBytesRemaining.js");
/* harmony import */ var _uppy_utils_lib_getTextDirection__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @uppy/utils/lib/getTextDirection */ "./src/jgDashboard/node_modules/@uppy/utils/lib/getTextDirection.js");
/* harmony import */ var _StatusBarStates_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./StatusBarStates.js */ "./src/jgDashboard/node_modules/@uppy/status-bar/lib/StatusBarStates.js");
/* harmony import */ var _StatusBarUI_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./StatusBarUI.js */ "./src/jgDashboard/node_modules/@uppy/status-bar/lib/StatusBarUI.js");
/* harmony import */ var _locale_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./locale.js */ "./src/jgDashboard/node_modules/@uppy/status-bar/lib/locale.js");






const packageJson = {
  "version": "3.1.2"
};


function getTotalSpeed(files) {
  let totalSpeed = 0;
  files.forEach(file => {
    totalSpeed += (0,_uppy_utils_lib_getSpeed__WEBPACK_IMPORTED_MODULE_1__["default"])(file.progress);
  });
  return totalSpeed;
}

function getTotalETA(files) {
  const totalSpeed = getTotalSpeed(files);

  if (totalSpeed === 0) {
    return 0;
  }

  const totalBytesRemaining = files.reduce((total, file) => {
    return total + (0,_uppy_utils_lib_getBytesRemaining__WEBPACK_IMPORTED_MODULE_2__["default"])(file.progress);
  }, 0);
  return Math.round(totalBytesRemaining / totalSpeed * 10) / 10;
}

function getUploadingState(error, isAllComplete, recoveredState, files) {
  if (error) {
    return _StatusBarStates_js__WEBPACK_IMPORTED_MODULE_4__["default"].STATE_ERROR;
  }

  if (isAllComplete) {
    return _StatusBarStates_js__WEBPACK_IMPORTED_MODULE_4__["default"].STATE_COMPLETE;
  }

  if (recoveredState) {
    return _StatusBarStates_js__WEBPACK_IMPORTED_MODULE_4__["default"].STATE_WAITING;
  }

  let state = _StatusBarStates_js__WEBPACK_IMPORTED_MODULE_4__["default"].STATE_WAITING;
  const fileIDs = Object.keys(files);

  for (let i = 0; i < fileIDs.length; i++) {
    const {
      progress
    } = files[fileIDs[i]]; // If ANY files are being uploaded right now, show the uploading state.

    if (progress.uploadStarted && !progress.uploadComplete) {
      return _StatusBarStates_js__WEBPACK_IMPORTED_MODULE_4__["default"].STATE_UPLOADING;
    } // If files are being preprocessed AND postprocessed at this time, we show the
    // preprocess state. If any files are being uploaded we show uploading.


    if (progress.preprocess && state !== _StatusBarStates_js__WEBPACK_IMPORTED_MODULE_4__["default"].STATE_UPLOADING) {
      state = _StatusBarStates_js__WEBPACK_IMPORTED_MODULE_4__["default"].STATE_PREPROCESSING;
    } // If NO files are being preprocessed or uploaded right now, but some files are
    // being postprocessed, show the postprocess state.


    if (progress.postprocess && state !== _StatusBarStates_js__WEBPACK_IMPORTED_MODULE_4__["default"].STATE_UPLOADING && state !== _StatusBarStates_js__WEBPACK_IMPORTED_MODULE_4__["default"].STATE_PREPROCESSING) {
      state = _StatusBarStates_js__WEBPACK_IMPORTED_MODULE_4__["default"].STATE_POSTPROCESSING;
    }
  }

  return state;
}
/**
 * StatusBar: renders a status bar with upload/pause/resume/cancel/retry buttons,
 * progress percentage and time remaining.
 */


class StatusBar extends _uppy_core__WEBPACK_IMPORTED_MODULE_0__.UIPlugin {
  constructor(uppy, opts) {
    super(uppy, opts);

    this.startUpload = () => {
      const {
        recoveredState
      } = this.uppy.getState();

      if (recoveredState) {
        this.uppy.emit('restore-confirmed');
        return undefined;
      }

      return this.uppy.upload().catch(() => {// Error logged in Core
      });
    };

    this.id = this.opts.id || 'StatusBar';
    this.title = 'StatusBar';
    this.type = 'progressindicator';
    this.defaultLocale = _locale_js__WEBPACK_IMPORTED_MODULE_6__["default"]; // set default options, must be kept in sync with @uppy/react/src/StatusBar.js

    const defaultOptions = {
      target: 'body',
      hideUploadButton: false,
      hideRetryButton: false,
      hidePauseResumeButton: false,
      hideCancelButton: false,
      showProgressDetails: false,
      hideAfterFinish: true,
      doneButtonHandler: null
    };
    this.opts = { ...defaultOptions,
      ...opts
    };
    this.i18nInit();
    this.render = this.render.bind(this);
    this.install = this.install.bind(this);
  }

  render(state) {
    const {
      capabilities,
      files,
      allowNewUpload,
      totalProgress,
      error,
      recoveredState
    } = state;
    const {
      newFiles,
      startedFiles,
      completeFiles,
      inProgressNotPausedFiles,
      isUploadStarted,
      isAllComplete,
      isAllErrored,
      isAllPaused,
      isUploadInProgress,
      isSomeGhost
    } = this.uppy.getObjectOfFilesPerState(); // If some state was recovered, we want to show Upload button/counter
    // for all the files, because in this case it’s not an Upload button,
    // but “Confirm Restore Button”

    const newFilesOrRecovered = recoveredState ? Object.values(files) : newFiles;
    const totalETA = getTotalETA(inProgressNotPausedFiles);
    const resumableUploads = !!capabilities.resumableUploads;
    const supportsUploadProgress = capabilities.uploadProgress !== false;
    let totalSize = 0;
    let totalUploadedSize = 0;
    startedFiles.forEach(file => {
      totalSize += file.progress.bytesTotal || 0;
      totalUploadedSize += file.progress.bytesUploaded || 0;
    });
    return (0,_StatusBarUI_js__WEBPACK_IMPORTED_MODULE_5__["default"])({
      error,
      uploadState: getUploadingState(error, isAllComplete, recoveredState, state.files || {}),
      allowNewUpload,
      totalProgress,
      totalSize,
      totalUploadedSize,
      isAllComplete: false,
      isAllPaused,
      isAllErrored,
      isUploadStarted,
      isUploadInProgress,
      isSomeGhost,
      recoveredState,
      complete: completeFiles.length,
      newFiles: newFilesOrRecovered.length,
      numUploads: startedFiles.length,
      totalETA,
      files,
      i18n: this.i18n,
      uppy: this.uppy,
      startUpload: this.startUpload,
      doneButtonHandler: this.opts.doneButtonHandler,
      resumableUploads,
      supportsUploadProgress,
      showProgressDetails: this.opts.showProgressDetails,
      hideUploadButton: this.opts.hideUploadButton,
      hideRetryButton: this.opts.hideRetryButton,
      hidePauseResumeButton: this.opts.hidePauseResumeButton,
      hideCancelButton: this.opts.hideCancelButton,
      hideAfterFinish: this.opts.hideAfterFinish,
      isTargetDOMEl: this.isTargetDOMEl
    });
  }

  onMount() {
    // Set the text direction if the page has not defined one.
    const element = this.el;
    const direction = (0,_uppy_utils_lib_getTextDirection__WEBPACK_IMPORTED_MODULE_3__["default"])(element);

    if (!direction) {
      element.dir = 'ltr';
    }
  }

  install() {
    const {
      target
    } = this.opts;

    if (target) {
      this.mount(target, this);
    }
  }

  uninstall() {
    this.unmount();
  }

}
StatusBar.VERSION = packageJson.version;

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/status-bar/lib/StatusBarStates.js":
/*!******************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/status-bar/lib/StatusBarStates.js ***!
  \******************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  STATE_ERROR: 'error',
  STATE_WAITING: 'waiting',
  STATE_PREPROCESSING: 'preprocessing',
  STATE_UPLOADING: 'uploading',
  STATE_POSTPROCESSING: 'postprocessing',
  STATE_COMPLETE: 'complete'
});

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/status-bar/lib/StatusBarUI.js":
/*!**************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/status-bar/lib/StatusBarUI.js ***!
  \**************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ StatusBar)
/* harmony export */ });
/* harmony import */ var preact__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! preact */ "./src/jgDashboard/node_modules/preact/dist/preact.module.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! classnames */ "./src/jgDashboard/node_modules/classnames/index.js");
/* harmony import */ var _StatusBarStates_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./StatusBarStates.js */ "./src/jgDashboard/node_modules/@uppy/status-bar/lib/StatusBarStates.js");
/* harmony import */ var _calculateProcessingProgress_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./calculateProcessingProgress.js */ "./src/jgDashboard/node_modules/@uppy/status-bar/lib/calculateProcessingProgress.js");
/* harmony import */ var _Components_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Components.js */ "./src/jgDashboard/node_modules/@uppy/status-bar/lib/Components.js");





const {
  STATE_ERROR,
  STATE_WAITING,
  STATE_PREPROCESSING,
  STATE_UPLOADING,
  STATE_POSTPROCESSING,
  STATE_COMPLETE
} = _StatusBarStates_js__WEBPACK_IMPORTED_MODULE_2__["default"]; // TODO: rename the function to StatusBarUI on the next major.

function StatusBar(props) {
  const {
    newFiles,
    allowNewUpload,
    isUploadInProgress,
    isAllPaused,
    resumableUploads,
    error,
    hideUploadButton,
    hidePauseResumeButton,
    hideCancelButton,
    hideRetryButton,
    recoveredState,
    uploadState,
    totalProgress,
    files,
    supportsUploadProgress,
    hideAfterFinish,
    isSomeGhost,
    doneButtonHandler,
    isUploadStarted,
    i18n,
    startUpload,
    uppy,
    isAllComplete,
    showProgressDetails,
    numUploads,
    complete,
    totalSize,
    totalETA,
    totalUploadedSize
  } = props;

  function getProgressValue() {
    switch (uploadState) {
      case STATE_POSTPROCESSING:
      case STATE_PREPROCESSING:
        {
          const progress = (0,_calculateProcessingProgress_js__WEBPACK_IMPORTED_MODULE_3__["default"])(files);

          if (progress.mode === 'determinate') {
            return progress.value * 100;
          }

          return totalProgress;
        }

      case STATE_ERROR:
        {
          return null;
        }

      case STATE_UPLOADING:
        {
          if (!supportsUploadProgress) {
            return null;
          }

          return totalProgress;
        }

      default:
        return totalProgress;
    }
  }

  function getIsIndeterminate() {
    switch (uploadState) {
      case STATE_POSTPROCESSING:
      case STATE_PREPROCESSING:
        {
          const {
            mode
          } = (0,_calculateProcessingProgress_js__WEBPACK_IMPORTED_MODULE_3__["default"])(files);
          return mode === 'indeterminate';
        }

      case STATE_UPLOADING:
        {
          if (!supportsUploadProgress) {
            return true;
          }

          return false;
        }

      default:
        return false;
    }
  }

  function getIsHidden() {
    if (recoveredState) {
      return false;
    }

    switch (uploadState) {
      case STATE_WAITING:
        return hideUploadButton || newFiles === 0;

      case STATE_COMPLETE:
        return hideAfterFinish;

      default:
        return false;
    }
  }

  const progressValue = getProgressValue();
  const isHidden = getIsHidden();
  const width = progressValue != null ? progressValue : 100;
  const showUploadBtn = !error && newFiles && !isUploadInProgress && !isAllPaused && allowNewUpload && !hideUploadButton;
  const showCancelBtn = !hideCancelButton && uploadState !== STATE_WAITING && uploadState !== STATE_COMPLETE;
  const showPauseResumeBtn = resumableUploads && !hidePauseResumeButton && uploadState === STATE_UPLOADING;
  const showRetryBtn = error && !isAllComplete && !hideRetryButton;
  const showDoneBtn = doneButtonHandler && uploadState === STATE_COMPLETE;
  const progressClassNames = classnames__WEBPACK_IMPORTED_MODULE_1__('uppy-StatusBar-progress', {
    'is-indeterminate': getIsIndeterminate()
  });
  const statusBarClassNames = classnames__WEBPACK_IMPORTED_MODULE_1__('uppy-StatusBar', `is-${uploadState}`, {
    'has-ghosts': isSomeGhost
  });
  return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: statusBarClassNames,
    "aria-hidden": isHidden
  }, (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: progressClassNames,
    style: {
      width: `${width}%`
    },
    role: "progressbar",
    "aria-label": `${width}%`,
    "aria-valuetext": `${width}%`,
    "aria-valuemin": "0",
    "aria-valuemax": "100",
    "aria-valuenow": progressValue
  }), (() => {
    switch (uploadState) {
      case STATE_PREPROCESSING:
      case STATE_POSTPROCESSING:
        return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_Components_js__WEBPACK_IMPORTED_MODULE_4__.ProgressBarProcessing, {
          progress: (0,_calculateProcessingProgress_js__WEBPACK_IMPORTED_MODULE_3__["default"])(files)
        });

      case STATE_COMPLETE:
        return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_Components_js__WEBPACK_IMPORTED_MODULE_4__.ProgressBarComplete, {
          i18n: i18n
        });

      case STATE_ERROR:
        return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_Components_js__WEBPACK_IMPORTED_MODULE_4__.ProgressBarError, {
          error: error,
          i18n: i18n,
          numUploads: numUploads,
          complete: complete
        });

      case STATE_UPLOADING:
        return (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_Components_js__WEBPACK_IMPORTED_MODULE_4__.ProgressBarUploading, {
          i18n: i18n,
          supportsUploadProgress: supportsUploadProgress,
          totalProgress: totalProgress,
          showProgressDetails: showProgressDetails,
          isUploadStarted: isUploadStarted,
          isAllComplete: isAllComplete,
          isAllPaused: isAllPaused,
          newFiles: newFiles,
          numUploads: numUploads,
          complete: complete,
          totalUploadedSize: totalUploadedSize,
          totalSize: totalSize,
          totalETA: totalETA,
          startUpload: startUpload
        });

      default:
        return null;
    }
  })(), (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)("div", {
    className: "uppy-StatusBar-actions"
  }, recoveredState || showUploadBtn ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_Components_js__WEBPACK_IMPORTED_MODULE_4__.UploadBtn, {
    newFiles: newFiles,
    isUploadStarted: isUploadStarted,
    recoveredState: recoveredState,
    i18n: i18n,
    isSomeGhost: isSomeGhost,
    startUpload: startUpload,
    uploadState: uploadState
  }) : null, showRetryBtn ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_Components_js__WEBPACK_IMPORTED_MODULE_4__.RetryBtn, {
    i18n: i18n,
    uppy: uppy
  }) : null, showPauseResumeBtn ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_Components_js__WEBPACK_IMPORTED_MODULE_4__.PauseResumeButton, {
    isAllPaused: isAllPaused,
    i18n: i18n,
    isAllComplete: isAllComplete,
    resumableUploads: resumableUploads,
    uppy: uppy
  }) : null, showCancelBtn ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_Components_js__WEBPACK_IMPORTED_MODULE_4__.CancelBtn, {
    i18n: i18n,
    uppy: uppy
  }) : null, showDoneBtn ? (0,preact__WEBPACK_IMPORTED_MODULE_0__.h)(_Components_js__WEBPACK_IMPORTED_MODULE_4__.DoneBtn, {
    i18n: i18n,
    doneButtonHandler: doneButtonHandler
  }) : null));
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/status-bar/lib/calculateProcessingProgress.js":
/*!******************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/status-bar/lib/calculateProcessingProgress.js ***!
  \******************************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ calculateProcessingProgress)
/* harmony export */ });
function calculateProcessingProgress(files) {
  const values = [];
  let mode;
  let message;

  for (const {
    progress
  } of Object.values(files)) {
    const {
      preprocess,
      postprocess
    } = progress; // In the future we should probably do this differently. For now we'll take the
    // mode and message from the first file…

    if (message == null && (preprocess || postprocess)) {
      ({
        mode,
        message
      } = preprocess || postprocess);
    }

    if ((preprocess == null ? void 0 : preprocess.mode) === 'determinate') values.push(preprocess.value);
    if ((postprocess == null ? void 0 : postprocess.mode) === 'determinate') values.push(postprocess.value);
  }

  const value = values.reduce((total, progressValue) => {
    return total + progressValue / values.length;
  }, 0);
  return {
    mode,
    message,
    value
  };
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/status-bar/lib/index.js":
/*!********************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/status-bar/lib/index.js ***!
  \********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _StatusBar_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _StatusBar_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./StatusBar.js */ "./src/jgDashboard/node_modules/@uppy/status-bar/lib/StatusBar.js");


/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/status-bar/lib/locale.js":
/*!*********************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/status-bar/lib/locale.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  strings: {
    // Shown in the status bar while files are being uploaded.
    uploading: 'Uploading',
    // Shown in the status bar once all files have been uploaded.
    complete: 'Complete',
    // Shown in the status bar if an upload failed.
    uploadFailed: 'Upload failed',
    // Shown in the status bar while the upload is paused.
    paused: 'Paused',
    // Used as the label for the button that retries an upload.
    retry: 'Retry',
    // Used as the label for the button that cancels an upload.
    cancel: 'Cancel',
    // Used as the label for the button that pauses an upload.
    pause: 'Pause',
    // Used as the label for the button that resumes an upload.
    resume: 'Resume',
    // Used as the label for the button that resets the upload state after an upload
    done: 'Done',
    // When `showProgressDetails` is set, shows the number of files that have been fully uploaded so far.
    filesUploadedOfTotal: {
      0: '%{complete} of %{smart_count} file uploaded',
      1: '%{complete} of %{smart_count} files uploaded'
    },
    // When `showProgressDetails` is set, shows the amount of bytes that have been uploaded so far.
    dataUploadedOfTotal: '%{complete} of %{total}',
    // When `showProgressDetails` is set, shows an estimation of how long the upload will take to complete.
    xTimeLeft: '%{time} left',
    // Used as the label for the button that starts an upload.
    uploadXFiles: {
      0: 'Upload %{smart_count} file',
      1: 'Upload %{smart_count} files'
    },
    // Used as the label for the button that starts an upload, if another upload has been started in the past
    // and new files were added later.
    uploadXNewFiles: {
      0: 'Upload +%{smart_count} file',
      1: 'Upload +%{smart_count} files'
    },
    upload: 'Upload',
    retryUpload: 'Retry upload',
    xMoreFilesAdded: {
      0: '%{smart_count} more file added',
      1: '%{smart_count} more files added'
    },
    showErrorDetails: 'Show error details'
  }
});

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/store-default/lib/index.js":
/*!***********************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/store-default/lib/index.js ***!
  \***********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
function _classPrivateFieldLooseBase(receiver, privateKey) { if (!Object.prototype.hasOwnProperty.call(receiver, privateKey)) { throw new TypeError("attempted to use private field on non-instance"); } return receiver; }

var id = 0;

function _classPrivateFieldLooseKey(name) { return "__private_" + id++ + "_" + name; }

const packageJson = {
  "version": "3.0.3"
};
/**
 * Default store that keeps state in a simple object.
 */

var _callbacks = /*#__PURE__*/_classPrivateFieldLooseKey("callbacks");

var _publish = /*#__PURE__*/_classPrivateFieldLooseKey("publish");

class DefaultStore {
  constructor() {
    Object.defineProperty(this, _publish, {
      value: _publish2
    });
    Object.defineProperty(this, _callbacks, {
      writable: true,
      value: new Set()
    });
    this.state = {};
  }

  getState() {
    return this.state;
  }

  setState(patch) {
    const prevState = { ...this.state
    };
    const nextState = { ...this.state,
      ...patch
    };
    this.state = nextState;

    _classPrivateFieldLooseBase(this, _publish)[_publish](prevState, nextState, patch);
  }

  subscribe(listener) {
    _classPrivateFieldLooseBase(this, _callbacks)[_callbacks].add(listener);

    return () => {
      _classPrivateFieldLooseBase(this, _callbacks)[_callbacks].delete(listener);
    };
  }

}

function _publish2() {
  for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
    args[_key] = arguments[_key];
  }

  _classPrivateFieldLooseBase(this, _callbacks)[_callbacks].forEach(listener => {
    listener(...args);
  });
}

DefaultStore.VERSION = packageJson.version;
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DefaultStore);

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/thumbnail-generator/lib/index.js":
/*!*****************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/thumbnail-generator/lib/index.js ***!
  \*****************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ ThumbnailGenerator)
/* harmony export */ });
/* harmony import */ var _uppy_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @uppy/core */ "./src/jgDashboard/node_modules/@uppy/core/lib/index.js");
/* harmony import */ var _uppy_utils_lib_dataURItoBlob__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @uppy/utils/lib/dataURItoBlob */ "./src/jgDashboard/node_modules/@uppy/utils/lib/dataURItoBlob.js");
/* harmony import */ var _uppy_utils_lib_isObjectURL__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @uppy/utils/lib/isObjectURL */ "./src/jgDashboard/node_modules/@uppy/utils/lib/isObjectURL.js");
/* harmony import */ var _uppy_utils_lib_isPreviewSupported__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @uppy/utils/lib/isPreviewSupported */ "./src/jgDashboard/node_modules/@uppy/utils/lib/isPreviewSupported.js");
/* harmony import */ var exifr_dist_mini_esm_mjs__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! exifr/dist/mini.esm.mjs */ "./src/jgDashboard/node_modules/exifr/dist/mini.esm.mjs");
/* harmony import */ var _locale_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./locale.js */ "./src/jgDashboard/node_modules/@uppy/thumbnail-generator/lib/locale.js");






const packageJson = {
  "version": "3.0.3"
};
/**
 * Save a <canvas> element's content to a Blob object.
 *
 * @param {HTMLCanvasElement} canvas
 * @returns {Promise}
 */

function canvasToBlob(canvas, type, quality) {
  try {
    canvas.getContext('2d').getImageData(0, 0, 1, 1);
  } catch (err) {
    if (err.code === 18) {
      return Promise.reject(new Error('cannot read image, probably an svg with external resources'));
    }
  }

  if (canvas.toBlob) {
    return new Promise(resolve => {
      canvas.toBlob(resolve, type, quality);
    }).then(blob => {
      if (blob === null) {
        throw new Error('cannot read image, probably an svg with external resources');
      }

      return blob;
    });
  }

  return Promise.resolve().then(() => {
    return (0,_uppy_utils_lib_dataURItoBlob__WEBPACK_IMPORTED_MODULE_1__["default"])(canvas.toDataURL(type, quality), {});
  }).then(blob => {
    if (blob === null) {
      throw new Error('could not extract blob, probably an old browser');
    }

    return blob;
  });
}

function rotateImage(image, translate) {
  let w = image.width;
  let h = image.height;

  if (translate.deg === 90 || translate.deg === 270) {
    w = image.height;
    h = image.width;
  }

  const canvas = document.createElement('canvas');
  canvas.width = w;
  canvas.height = h;
  const context = canvas.getContext('2d');
  context.translate(w / 2, h / 2);

  if (translate.canvas) {
    context.rotate(translate.rad);
    context.scale(translate.scaleX, translate.scaleY);
  }

  context.drawImage(image, -image.width / 2, -image.height / 2, image.width, image.height);
  return canvas;
}
/**
 * Make sure the image doesn’t exceed browser/device canvas limits.
 * For ios with 256 RAM and ie
 */


function protect(image) {
  // https://stackoverflow.com/questions/6081483/maximum-size-of-a-canvas-element
  const ratio = image.width / image.height;
  const maxSquare = 5000000; // ios max canvas square

  const maxSize = 4096; // ie max canvas dimensions

  let maxW = Math.floor(Math.sqrt(maxSquare * ratio));
  let maxH = Math.floor(maxSquare / Math.sqrt(maxSquare * ratio));

  if (maxW > maxSize) {
    maxW = maxSize;
    maxH = Math.round(maxW / ratio);
  }

  if (maxH > maxSize) {
    maxH = maxSize;
    maxW = Math.round(ratio * maxH);
  }

  if (image.width > maxW) {
    const canvas = document.createElement('canvas');
    canvas.width = maxW;
    canvas.height = maxH;
    canvas.getContext('2d').drawImage(image, 0, 0, maxW, maxH);
    return canvas;
  }

  return image;
}
/**
 * The Thumbnail Generator plugin
 */


class ThumbnailGenerator extends _uppy_core__WEBPACK_IMPORTED_MODULE_0__.UIPlugin {
  constructor(uppy, opts) {
    super(uppy, opts);

    this.onFileAdded = file => {
      if (!file.preview && file.data && (0,_uppy_utils_lib_isPreviewSupported__WEBPACK_IMPORTED_MODULE_3__["default"])(file.type) && !file.isRemote) {
        this.addToQueue(file.id);
      }
    };

    this.onCancelRequest = file => {
      const index = this.queue.indexOf(file.id);

      if (index !== -1) {
        this.queue.splice(index, 1);
      }
    };

    this.onFileRemoved = file => {
      const index = this.queue.indexOf(file.id);

      if (index !== -1) {
        this.queue.splice(index, 1);
      } // Clean up object URLs.


      if (file.preview && (0,_uppy_utils_lib_isObjectURL__WEBPACK_IMPORTED_MODULE_2__["default"])(file.preview)) {
        URL.revokeObjectURL(file.preview);
      }
    };

    this.onRestored = () => {
      const restoredFiles = this.uppy.getFiles().filter(file => file.isRestored);
      restoredFiles.forEach(file => {
        // Only add blob URLs; they are likely invalid after being restored.
        if (!file.preview || (0,_uppy_utils_lib_isObjectURL__WEBPACK_IMPORTED_MODULE_2__["default"])(file.preview)) {
          this.addToQueue(file.id);
        }
      });
    };

    this.onAllFilesRemoved = () => {
      this.queue = [];
    };

    this.waitUntilAllProcessed = fileIDs => {
      fileIDs.forEach(fileID => {
        const file = this.uppy.getFile(fileID);
        this.uppy.emit('preprocess-progress', file, {
          mode: 'indeterminate',
          message: this.i18n('generatingThumbnails')
        });
      });

      const emitPreprocessCompleteForAll = () => {
        fileIDs.forEach(fileID => {
          const file = this.uppy.getFile(fileID);
          this.uppy.emit('preprocess-complete', file);
        });
      };

      return new Promise(resolve => {
        if (this.queueProcessing) {
          this.uppy.once('thumbnail:all-generated', () => {
            emitPreprocessCompleteForAll();
            resolve();
          });
        } else {
          emitPreprocessCompleteForAll();
          resolve();
        }
      });
    };

    this.type = 'modifier';
    this.id = this.opts.id || 'ThumbnailGenerator';
    this.title = 'Thumbnail Generator';
    this.queue = [];
    this.queueProcessing = false;
    this.defaultThumbnailDimension = 200;
    this.thumbnailType = this.opts.thumbnailType || 'image/jpeg';
    this.defaultLocale = _locale_js__WEBPACK_IMPORTED_MODULE_5__["default"];
    const defaultOptions = {
      thumbnailWidth: null,
      thumbnailHeight: null,
      waitForThumbnailsBeforeUpload: false,
      lazy: false
    };
    this.opts = { ...defaultOptions,
      ...opts
    };
    this.i18nInit();

    if (this.opts.lazy && this.opts.waitForThumbnailsBeforeUpload) {
      throw new Error('ThumbnailGenerator: The `lazy` and `waitForThumbnailsBeforeUpload` options are mutually exclusive. Please ensure at most one of them is set to `true`.');
    }
  }
  /**
   * Create a thumbnail for the given Uppy file object.
   *
   * @param {{data: Blob}} file
   * @param {number} targetWidth
   * @param {number} targetHeight
   * @returns {Promise}
   */


  createThumbnail(file, targetWidth, targetHeight) {
    const originalUrl = URL.createObjectURL(file.data);
    const onload = new Promise((resolve, reject) => {
      const image = new Image();
      image.src = originalUrl;
      image.addEventListener('load', () => {
        URL.revokeObjectURL(originalUrl);
        resolve(image);
      });
      image.addEventListener('error', event => {
        URL.revokeObjectURL(originalUrl);
        reject(event.error || new Error('Could not create thumbnail'));
      });
    });
    const orientationPromise = (0,exifr_dist_mini_esm_mjs__WEBPACK_IMPORTED_MODULE_4__.rotation)(file.data).catch(() => 1);
    return Promise.all([onload, orientationPromise]).then(_ref => {
      let [image, orientation] = _ref;
      const dimensions = this.getProportionalDimensions(image, targetWidth, targetHeight, orientation.deg);
      const rotatedImage = rotateImage(image, orientation);
      const resizedImage = this.resizeImage(rotatedImage, dimensions.width, dimensions.height);
      return canvasToBlob(resizedImage, this.thumbnailType, 80);
    }).then(blob => {
      return URL.createObjectURL(blob);
    });
  }
  /**
   * Get the new calculated dimensions for the given image and a target width
   * or height. If both width and height are given, only width is taken into
   * account. If neither width nor height are given, the default dimension
   * is used.
   */


  getProportionalDimensions(img, width, height, rotation) {
    // eslint-disable-line no-shadow
    let aspect = img.width / img.height;

    if (rotation === 90 || rotation === 270) {
      aspect = img.height / img.width;
    }

    if (width != null) {
      return {
        width,
        height: Math.round(width / aspect)
      };
    }

    if (height != null) {
      return {
        width: Math.round(height * aspect),
        height
      };
    }

    return {
      width: this.defaultThumbnailDimension,
      height: Math.round(this.defaultThumbnailDimension / aspect)
    };
  }
  /**
   * Resize an image to the target `width` and `height`.
   *
   * Returns a Canvas with the resized image on it.
   */
  // eslint-disable-next-line class-methods-use-this


  resizeImage(image, targetWidth, targetHeight) {
    // Resizing in steps refactored to use a solution from
    // https://blog.uploadcare.com/image-resize-in-browsers-is-broken-e38eed08df01
    let img = protect(image);
    let steps = Math.ceil(Math.log2(img.width / targetWidth));

    if (steps < 1) {
      steps = 1;
    }

    let sW = targetWidth * 2 ** (steps - 1);
    let sH = targetHeight * 2 ** (steps - 1);
    const x = 2;

    while (steps--) {
      const canvas = document.createElement('canvas');
      canvas.width = sW;
      canvas.height = sH;
      canvas.getContext('2d').drawImage(img, 0, 0, sW, sH);
      img = canvas;
      sW = Math.round(sW / x);
      sH = Math.round(sH / x);
    }

    return img;
  }
  /**
   * Set the preview URL for a file.
   */


  setPreviewURL(fileID, preview) {
    this.uppy.setFileState(fileID, {
      preview
    });
  }

  addToQueue(item) {
    this.queue.push(item);

    if (this.queueProcessing === false) {
      this.processQueue();
    }
  }

  processQueue() {
    this.queueProcessing = true;

    if (this.queue.length > 0) {
      const current = this.uppy.getFile(this.queue.shift());

      if (!current) {
        this.uppy.log('[ThumbnailGenerator] file was removed before a thumbnail could be generated, but not removed from the queue. This is probably a bug', 'error');
        return Promise.resolve();
      }

      return this.requestThumbnail(current).catch(() => {}) // eslint-disable-line node/handle-callback-err
      .then(() => this.processQueue());
    }

    this.queueProcessing = false;
    this.uppy.log('[ThumbnailGenerator] Emptied thumbnail queue');
    this.uppy.emit('thumbnail:all-generated');
    return Promise.resolve();
  }

  requestThumbnail(file) {
    if ((0,_uppy_utils_lib_isPreviewSupported__WEBPACK_IMPORTED_MODULE_3__["default"])(file.type) && !file.isRemote) {
      return this.createThumbnail(file, this.opts.thumbnailWidth, this.opts.thumbnailHeight).then(preview => {
        this.setPreviewURL(file.id, preview);
        this.uppy.log(`[ThumbnailGenerator] Generated thumbnail for ${file.id}`);
        this.uppy.emit('thumbnail:generated', this.uppy.getFile(file.id), preview);
      }).catch(err => {
        this.uppy.log(`[ThumbnailGenerator] Failed thumbnail for ${file.id}:`, 'warning');
        this.uppy.log(err, 'warning');
        this.uppy.emit('thumbnail:error', this.uppy.getFile(file.id), err);
      });
    }

    return Promise.resolve();
  }

  install() {
    this.uppy.on('file-removed', this.onFileRemoved);
    this.uppy.on('cancel-all', this.onAllFilesRemoved);

    if (this.opts.lazy) {
      this.uppy.on('thumbnail:request', this.onFileAdded);
      this.uppy.on('thumbnail:cancel', this.onCancelRequest);
    } else {
      this.uppy.on('file-added', this.onFileAdded);
      this.uppy.on('restored', this.onRestored);
    }

    if (this.opts.waitForThumbnailsBeforeUpload) {
      this.uppy.addPreProcessor(this.waitUntilAllProcessed);
    }
  }

  uninstall() {
    this.uppy.off('file-removed', this.onFileRemoved);
    this.uppy.off('cancel-all', this.onAllFilesRemoved);

    if (this.opts.lazy) {
      this.uppy.off('thumbnail:request', this.onFileAdded);
      this.uppy.off('thumbnail:cancel', this.onCancelRequest);
    } else {
      this.uppy.off('file-added', this.onFileAdded);
      this.uppy.off('restored', this.onRestored);
    }

    if (this.opts.waitForThumbnailsBeforeUpload) {
      this.uppy.removePreProcessor(this.waitUntilAllProcessed);
    }
  }

}
ThumbnailGenerator.VERSION = packageJson.version;

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/thumbnail-generator/lib/locale.js":
/*!******************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/thumbnail-generator/lib/locale.js ***!
  \******************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  strings: {
    generatingThumbnails: 'Generating thumbnails...'
  }
});

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/FOCUSABLE_ELEMENTS.js":
/*!****************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/FOCUSABLE_ELEMENTS.js ***!
  \****************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (['a[href]:not([tabindex^="-"]):not([inert]):not([aria-hidden])', 'area[href]:not([tabindex^="-"]):not([inert]):not([aria-hidden])', 'input:not([disabled]):not([inert]):not([aria-hidden])', 'select:not([disabled]):not([inert]):not([aria-hidden])', 'textarea:not([disabled]):not([inert]):not([aria-hidden])', 'button:not([disabled]):not([inert]):not([aria-hidden])', 'iframe:not([tabindex^="-"]):not([inert]):not([aria-hidden])', 'object:not([tabindex^="-"]):not([inert]):not([aria-hidden])', 'embed:not([tabindex^="-"]):not([inert]):not([aria-hidden])', '[contenteditable]:not([tabindex^="-"]):not([inert]):not([aria-hidden])', '[tabindex]:not([tabindex^="-"]):not([inert]):not([aria-hidden])']);

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/Translator.js":
/*!********************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/Translator.js ***!
  \********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Translator)
/* harmony export */ });
/* harmony import */ var _hasProperty_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./hasProperty.js */ "./src/jgDashboard/node_modules/@uppy/utils/lib/hasProperty.js");
function _classPrivateFieldLooseBase(receiver, privateKey) { if (!Object.prototype.hasOwnProperty.call(receiver, privateKey)) { throw new TypeError("attempted to use private field on non-instance"); } return receiver; }

var id = 0;

function _classPrivateFieldLooseKey(name) { return "__private_" + id++ + "_" + name; }



function insertReplacement(source, rx, replacement) {
  const newParts = [];
  source.forEach(chunk => {
    // When the source contains multiple placeholders for interpolation,
    // we should ignore chunks that are not strings, because those
    // can be JSX objects and will be otherwise incorrectly turned into strings.
    // Without this condition we’d get this: [object Object] hello [object Object] my <button>
    if (typeof chunk !== 'string') {
      return newParts.push(chunk);
    }

    return rx[Symbol.split](chunk).forEach((raw, i, list) => {
      if (raw !== '') {
        newParts.push(raw);
      } // Interlace with the `replacement` value


      if (i < list.length - 1) {
        newParts.push(replacement);
      }
    });
  });
  return newParts;
}
/**
 * Takes a string with placeholder variables like `%{smart_count} file selected`
 * and replaces it with values from options `{smart_count: 5}`
 *
 * @license https://github.com/airbnb/polyglot.js/blob/master/LICENSE
 * taken from https://github.com/airbnb/polyglot.js/blob/master/lib/polyglot.js#L299
 *
 * @param {string} phrase that needs interpolation, with placeholders
 * @param {object} options with values that will be used to replace placeholders
 * @returns {any[]} interpolated
 */


function interpolate(phrase, options) {
  const dollarRegex = /\$/g;
  const dollarBillsYall = '$$$$';
  let interpolated = [phrase];
  if (options == null) return interpolated;

  for (const arg of Object.keys(options)) {
    if (arg !== '_') {
      // Ensure replacement value is escaped to prevent special $-prefixed
      // regex replace tokens. the "$$$$" is needed because each "$" needs to
      // be escaped with "$" itself, and we need two in the resulting output.
      let replacement = options[arg];

      if (typeof replacement === 'string') {
        replacement = dollarRegex[Symbol.replace](replacement, dollarBillsYall);
      } // We create a new `RegExp` each time instead of using a more-efficient
      // string replace so that the same argument can be replaced multiple times
      // in the same phrase.


      interpolated = insertReplacement(interpolated, new RegExp(`%\\{${arg}\\}`, 'g'), replacement);
    }
  }

  return interpolated;
}
/**
 * Translates strings with interpolation & pluralization support.
 * Extensible with custom dictionaries and pluralization functions.
 *
 * Borrows heavily from and inspired by Polyglot https://github.com/airbnb/polyglot.js,
 * basically a stripped-down version of it. Differences: pluralization functions are not hardcoded
 * and can be easily added among with dictionaries, nested objects are used for pluralization
 * as opposed to `||||` delimeter
 *
 * Usage example: `translator.translate('files_chosen', {smart_count: 3})`
 */


var _apply = /*#__PURE__*/_classPrivateFieldLooseKey("apply");

class Translator {
  /**
   * @param {object|Array<object>} locales - locale or list of locales.
   */
  constructor(locales) {
    Object.defineProperty(this, _apply, {
      value: _apply2
    });
    this.locale = {
      strings: {},

      pluralize(n) {
        if (n === 1) {
          return 0;
        }

        return 1;
      }

    };

    if (Array.isArray(locales)) {
      locales.forEach(_classPrivateFieldLooseBase(this, _apply)[_apply], this);
    } else {
      _classPrivateFieldLooseBase(this, _apply)[_apply](locales);
    }
  }

  /**
   * Public translate method
   *
   * @param {string} key
   * @param {object} options with values that will be used later to replace placeholders in string
   * @returns {string} translated (and interpolated)
   */
  translate(key, options) {
    return this.translateArray(key, options).join('');
  }
  /**
   * Get a translation and return the translated and interpolated parts as an array.
   *
   * @param {string} key
   * @param {object} options with values that will be used to replace placeholders
   * @returns {Array} The translated and interpolated parts, in order.
   */


  translateArray(key, options) {
    if (!(0,_hasProperty_js__WEBPACK_IMPORTED_MODULE_0__["default"])(this.locale.strings, key)) {
      throw new Error(`missing string: ${key}`);
    }

    const string = this.locale.strings[key];
    const hasPluralForms = typeof string === 'object';

    if (hasPluralForms) {
      if (options && typeof options.smart_count !== 'undefined') {
        const plural = this.locale.pluralize(options.smart_count);
        return interpolate(string[plural], options);
      }

      throw new Error('Attempted to use a string with plural forms, but no value was given for %{smart_count}');
    }

    return interpolate(string, options);
  }

}

function _apply2(locale) {
  if (!(locale != null && locale.strings)) {
    return;
  }

  const prevLocale = this.locale;
  this.locale = { ...prevLocale,
    strings: { ...prevLocale.strings,
      ...locale.strings
    }
  };
  this.locale.pluralize = locale.pluralize || prevLocale.pluralize;
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/dataURItoBlob.js":
/*!***********************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/dataURItoBlob.js ***!
  \***********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ dataURItoBlob)
/* harmony export */ });
const DATA_URL_PATTERN = /^data:([^/]+\/[^,;]+(?:[^,]*?))(;base64)?,([\s\S]*)$/;
function dataURItoBlob(dataURI, opts, toFile) {
  var _ref, _opts$mimeType;

  // get the base64 data
  const dataURIData = DATA_URL_PATTERN.exec(dataURI); // user may provide mime type, if not get it from data URI

  const mimeType = (_ref = (_opts$mimeType = opts.mimeType) != null ? _opts$mimeType : dataURIData == null ? void 0 : dataURIData[1]) != null ? _ref : 'plain/text';
  let data;

  if (dataURIData[2] != null) {
    const binary = atob(decodeURIComponent(dataURIData[3]));
    const bytes = new Uint8Array(binary.length);

    for (let i = 0; i < binary.length; i++) {
      bytes[i] = binary.charCodeAt(i);
    }

    data = [bytes];
  } else {
    data = [decodeURIComponent(dataURIData[3])];
  } // Convert to a File?


  if (toFile) {
    return new File(data, opts.name || '', {
      type: mimeType
    });
  }

  return new Blob(data, {
    type: mimeType
  });
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/findAllDOMElements.js":
/*!****************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/findAllDOMElements.js ***!
  \****************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ findAllDOMElements)
/* harmony export */ });
/* harmony import */ var _isDOMElement_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./isDOMElement.js */ "./src/jgDashboard/node_modules/@uppy/utils/lib/isDOMElement.js");

/**
 * Find one or more DOM elements.
 *
 * @param {string|Node} element
 * @returns {Node[]|null}
 */

function findAllDOMElements(element) {
  if (typeof element === 'string') {
    const elements = document.querySelectorAll(element);
    return elements.length === 0 ? null : Array.from(elements);
  }

  if (typeof element === 'object' && (0,_isDOMElement_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element)) {
    return [element];
  }

  return null;
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/findDOMElement.js":
/*!************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/findDOMElement.js ***!
  \************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ findDOMElement)
/* harmony export */ });
/* harmony import */ var _isDOMElement_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./isDOMElement.js */ "./src/jgDashboard/node_modules/@uppy/utils/lib/isDOMElement.js");

/**
 * Find a DOM element.
 *
 * @param {Node|string} element
 * @returns {Node|null}
 */

function findDOMElement(element, context) {
  if (context === void 0) {
    context = document;
  }

  if (typeof element === 'string') {
    return context.querySelector(element);
  }

  if ((0,_isDOMElement_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element)) {
    return element;
  }

  return null;
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/generateFileID.js":
/*!************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/generateFileID.js ***!
  \************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ generateFileID),
/* harmony export */   getSafeFileId: () => (/* binding */ getSafeFileId)
/* harmony export */ });
/* harmony import */ var _getFileType_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getFileType.js */ "./src/jgDashboard/node_modules/@uppy/utils/lib/getFileType.js");


function encodeCharacter(character) {
  return character.charCodeAt(0).toString(32);
}

function encodeFilename(name) {
  let suffix = '';
  return name.replace(/[^A-Z0-9]/ig, character => {
    suffix += `-${encodeCharacter(character)}`;
    return '/';
  }) + suffix;
}
/**
 * Takes a file object and turns it into fileID, by converting file.name to lowercase,
 * removing extra characters and adding type, size and lastModified
 *
 * @param {object} file
 * @returns {string} the fileID
 */


function generateFileID(file) {
  // It's tempting to do `[items].filter(Boolean).join('-')` here, but that
  // is slower! simple string concatenation is fast
  let id = 'uppy';

  if (typeof file.name === 'string') {
    id += `-${encodeFilename(file.name.toLowerCase())}`;
  }

  if (file.type !== undefined) {
    id += `-${file.type}`;
  }

  if (file.meta && typeof file.meta.relativePath === 'string') {
    id += `-${encodeFilename(file.meta.relativePath.toLowerCase())}`;
  }

  if (file.data.size !== undefined) {
    id += `-${file.data.size}`;
  }

  if (file.data.lastModified !== undefined) {
    id += `-${file.data.lastModified}`;
  }

  return id;
} // If the provider has a stable, unique ID, then we can use that to identify the file.
// Then we don't have to generate our own ID, and we can add the same file many times if needed (different path)

function hasFileStableId(file) {
  if (!file.isRemote || !file.remote) return false; // These are the providers that it seems like have stable IDs for their files. The other's I haven't checked yet.

  const stableIdProviders = new Set(['box', 'dropbox', 'drive', 'facebook', 'unsplash']);
  return stableIdProviders.has(file.remote.provider);
}

function getSafeFileId(file) {
  if (hasFileStableId(file)) return file.id;
  const fileType = (0,_getFileType_js__WEBPACK_IMPORTED_MODULE_0__["default"])(file);
  return generateFileID({ ...file,
    type: fileType
  });
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/getBytesRemaining.js":
/*!***************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/getBytesRemaining.js ***!
  \***************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getBytesRemaining)
/* harmony export */ });
function getBytesRemaining(fileProgress) {
  return fileProgress.bytesTotal - fileProgress.bytesUploaded;
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/getDroppedFiles/index.js":
/*!*******************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/getDroppedFiles/index.js ***!
  \*******************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getDroppedFiles)
/* harmony export */ });
/* harmony import */ var _utils_webkitGetAsEntryApi_index_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./utils/webkitGetAsEntryApi/index.js */ "./src/jgDashboard/node_modules/@uppy/utils/lib/getDroppedFiles/utils/webkitGetAsEntryApi/index.js");
/* harmony import */ var _utils_fallbackApi_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./utils/fallbackApi.js */ "./src/jgDashboard/node_modules/@uppy/utils/lib/getDroppedFiles/utils/fallbackApi.js");


/**
 * Returns a promise that resolves to the array of dropped files (if a folder is
 * dropped, and browser supports folder parsing - promise resolves to the flat
 * array of all files in all directories).
 * Each file has .relativePath prop appended to it (e.g. "/docs/Prague/ticket_from_prague_to_ufa.pdf")
 * if browser supports it. Otherwise it's undefined.
 *
 * @param {DataTransfer} dataTransfer
 * @param {Function} logDropError - a function that's called every time some
 * folder or some file error out (e.g. because of the folder name being too long
 * on Windows). Notice that resulting promise will always be resolved anyway.
 *
 * @returns {Promise} - Array<File>
 */

async function getDroppedFiles(dataTransfer, _temp) {
  let {
    logDropError = () => {}
  } = _temp === void 0 ? {} : _temp;

  // Get all files from all subdirs. Works (at least) in Chrome, Mozilla, and Safari
  try {
    const accumulator = [];

    for await (const file of (0,_utils_webkitGetAsEntryApi_index_js__WEBPACK_IMPORTED_MODULE_0__["default"])(dataTransfer, logDropError)) {
      accumulator.push(file);
    }

    return accumulator; // Otherwise just return all first-order files
  } catch {
    return (0,_utils_fallbackApi_js__WEBPACK_IMPORTED_MODULE_1__["default"])(dataTransfer);
  }
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/getDroppedFiles/utils/fallbackApi.js":
/*!*******************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/getDroppedFiles/utils/fallbackApi.js ***!
  \*******************************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ fallbackApi)
/* harmony export */ });
/* harmony import */ var _toArray_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../toArray.js */ "./src/jgDashboard/node_modules/@uppy/utils/lib/toArray.js");
 // .files fallback, should be implemented in any browser

function fallbackApi(dataTransfer) {
  const files = (0,_toArray_js__WEBPACK_IMPORTED_MODULE_0__["default"])(dataTransfer.files);
  return Promise.resolve(files);
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/getDroppedFiles/utils/webkitGetAsEntryApi/getFilesAndDirectoriesFromDirectory.js":
/*!***************************************************************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/getDroppedFiles/utils/webkitGetAsEntryApi/getFilesAndDirectoriesFromDirectory.js ***!
  \***************************************************************************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getFilesAndDirectoriesFromDirectory)
/* harmony export */ });
/**
 * Recursive function, calls the original callback() when the directory is entirely parsed.
 *
 * @param {FileSystemDirectoryReader} directoryReader
 * @param {Array} oldEntries
 * @param {Function} logDropError
 * @param {Function} callback - called with ([ all files and directories in that directoryReader ])
 */
function getFilesAndDirectoriesFromDirectory(directoryReader, oldEntries, logDropError, _ref) {
  let {
    onSuccess
  } = _ref;
  directoryReader.readEntries(entries => {
    const newEntries = [...oldEntries, ...entries]; // According to the FileSystem API spec, getFilesAndDirectoriesFromDirectory()
    // must be called until it calls the onSuccess with an empty array.

    if (entries.length) {
      queueMicrotask(() => {
        getFilesAndDirectoriesFromDirectory(directoryReader, newEntries, logDropError, {
          onSuccess
        });
      }); // Done iterating this particular directory
    } else {
      onSuccess(newEntries);
    }
  }, // Make sure we resolve on error anyway, it's fine if only one directory couldn't be parsed!
  error => {
    logDropError(error);
    onSuccess(oldEntries);
  });
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/getDroppedFiles/utils/webkitGetAsEntryApi/index.js":
/*!*********************************************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/getDroppedFiles/utils/webkitGetAsEntryApi/index.js ***!
  \*********************************************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getFilesFromDataTransfer)
/* harmony export */ });
/* harmony import */ var _getFilesAndDirectoriesFromDirectory_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getFilesAndDirectoriesFromDirectory.js */ "./src/jgDashboard/node_modules/@uppy/utils/lib/getDroppedFiles/utils/webkitGetAsEntryApi/getFilesAndDirectoriesFromDirectory.js");

/**
 * Polyfill for the new (experimental) getAsFileSystemHandle API (using the popular webkitGetAsEntry behind the scenes)
 * so that we can switch to the getAsFileSystemHandle API once it (hopefully) becomes standard
 */

function getAsFileSystemHandleFromEntry(entry, logDropError) {
  if (entry == null) return entry;
  return {
    // eslint-disable-next-line no-nested-ternary
    kind: entry.isFile ? 'file' : entry.isDirectory ? 'directory' : undefined,
    name: entry.name,

    getFile() {
      return new Promise((resolve, reject) => entry.file(resolve, reject));
    },

    async *values() {
      // If the file is a directory.
      const directoryReader = entry.createReader();
      const entries = await new Promise(resolve => {
        (0,_getFilesAndDirectoriesFromDirectory_js__WEBPACK_IMPORTED_MODULE_0__["default"])(directoryReader, [], logDropError, {
          onSuccess: dirEntries => resolve(dirEntries.map(file => getAsFileSystemHandleFromEntry(file, logDropError)))
        });
      });
      yield* entries;
    }

  };
}

async function* createPromiseToAddFileOrParseDirectory(entry, relativePath, lastResortFile) {
  if (lastResortFile === void 0) {
    lastResortFile = undefined;
  }

  // For each dropped item, - make sure it's a file/directory, and start deepening in!
  if (entry.kind === 'file') {
    const file = await entry.getFile();

    if (file != null) {
      file.relativePath = relativePath ? `${relativePath}/${entry.name}` : null;
      yield file;
    } else if (lastResortFile != null) yield lastResortFile;
  } else if (entry.kind === 'directory') {
    for await (const handle of entry.values()) {
      // Recurse on the directory, appending the dir name to the relative path
      yield* createPromiseToAddFileOrParseDirectory(handle, `${relativePath}/${entry.name}`);
    }
  } else if (lastResortFile != null) yield lastResortFile;
}
/**
 * Load all files from data transfer, and recursively read any directories.
 * Note that IE is not supported for drag-drop, because IE doesn't support Data Transfers
 *
 * @param {DataTransfer} dataTransfer
 * @param {*} logDropError on error
 */


async function* getFilesFromDataTransfer(dataTransfer, logDropError) {
  // Retrieving the dropped items must happen synchronously
  // otherwise only the first item gets treated and the other ones are garbage collected.
  // https://github.com/transloadit/uppy/pull/3998
  const fileSystemHandles = await Promise.all(Array.from(dataTransfer.items, async item => {
    var _fileSystemHandle;

    let fileSystemHandle; // TODO enable getAsFileSystemHandle API once we can get it working with subdirectories
    // IMPORTANT: Need to check isSecureContext *before* calling getAsFileSystemHandle
    // or else Chrome will crash when running in HTTP: https://github.com/transloadit/uppy/issues/4133
    // if (window.isSecureContext && item.getAsFileSystemHandle != null) entry = await item.getAsFileSystemHandle()
    // `webkitGetAsEntry` exists in all popular browsers (including non-WebKit browsers),
    // however it may be renamed to getAsEntry() in the future, so you should code defensively, looking for both.
    // from https://developer.mozilla.org/en-US/docs/Web/API/DataTransferItem/webkitGetAsEntry

    const getAsEntry = () => typeof item.getAsEntry === 'function' ? item.getAsEntry() : item.webkitGetAsEntry(); // eslint-disable-next-line prefer-const


    (_fileSystemHandle = fileSystemHandle) != null ? _fileSystemHandle : fileSystemHandle = getAsFileSystemHandleFromEntry(getAsEntry(), logDropError);
    return {
      fileSystemHandle,
      lastResortFile: item.getAsFile() // can be used as a fallback in case other methods fail

    };
  }));

  for (const {
    lastResortFile,
    fileSystemHandle
  } of fileSystemHandles) {
    // fileSystemHandle and lastResortFile can be null when we drop an url.
    if (fileSystemHandle != null) {
      try {
        yield* createPromiseToAddFileOrParseDirectory(fileSystemHandle, '', lastResortFile);
      } catch (err) {
        // Example: If dropping a symbolic link, Chromium will throw:
        // "DOMException: A requested file or directory could not be found at the time an operation was processed.",
        // So we will use lastResortFile instead. See https://github.com/transloadit/uppy/issues/3505.
        if (lastResortFile != null) {
          yield lastResortFile;
        } else {
          logDropError(err);
        }
      }
    } else if (lastResortFile != null) yield lastResortFile;
  }
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/getFileNameAndExtension.js":
/*!*********************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/getFileNameAndExtension.js ***!
  \*********************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getFileNameAndExtension)
/* harmony export */ });
/**
 * Takes a full filename string and returns an object {name, extension}
 *
 * @param {string} fullFileName
 * @returns {object} {name, extension}
 */
function getFileNameAndExtension(fullFileName) {
  const lastDot = fullFileName.lastIndexOf('.'); // these count as no extension: "no-dot", "trailing-dot."

  if (lastDot === -1 || lastDot === fullFileName.length - 1) {
    return {
      name: fullFileName,
      extension: undefined
    };
  }

  return {
    name: fullFileName.slice(0, lastDot),
    extension: fullFileName.slice(lastDot + 1)
  };
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/getFileType.js":
/*!*********************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/getFileType.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getFileType)
/* harmony export */ });
/* harmony import */ var _getFileNameAndExtension_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getFileNameAndExtension.js */ "./src/jgDashboard/node_modules/@uppy/utils/lib/getFileNameAndExtension.js");
/* harmony import */ var _mimeTypes_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./mimeTypes.js */ "./src/jgDashboard/node_modules/@uppy/utils/lib/mimeTypes.js");


function getFileType(file) {
  var _getFileNameAndExtens;

  if (file.type) return file.type;
  const fileExtension = file.name ? (_getFileNameAndExtens = (0,_getFileNameAndExtension_js__WEBPACK_IMPORTED_MODULE_0__["default"])(file.name).extension) == null ? void 0 : _getFileNameAndExtens.toLowerCase() : null;

  if (fileExtension && fileExtension in _mimeTypes_js__WEBPACK_IMPORTED_MODULE_1__["default"]) {
    // else, see if we can map extension to a mime type
    return _mimeTypes_js__WEBPACK_IMPORTED_MODULE_1__["default"][fileExtension];
  } // if all fails, fall back to a generic byte stream type


  return 'application/octet-stream';
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/getSpeed.js":
/*!******************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/getSpeed.js ***!
  \******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getSpeed)
/* harmony export */ });
function getSpeed(fileProgress) {
  if (!fileProgress.bytesUploaded) return 0;
  const timeElapsed = Date.now() - fileProgress.uploadStarted;
  const uploadSpeed = fileProgress.bytesUploaded / (timeElapsed / 1000);
  return uploadSpeed;
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/getTextDirection.js":
/*!**************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/getTextDirection.js ***!
  \**************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * Get the declared text direction for an element.
 *
 * @param {Node} element
 * @returns {string|undefined}
 */
function getTextDirection(element) {
  var _element;

  // There is another way to determine text direction using getComputedStyle(), as done here:
  // https://github.com/pencil-js/text-direction/blob/2a235ce95089b3185acec3b51313cbba921b3811/text-direction.js
  //
  // We do not use that approach because we are interested specifically in the _declared_ text direction.
  // If no text direction is declared, we have to provide our own explicit text direction so our
  // bidirectional CSS style sheets work.
  while (element && !element.dir) {
    // eslint-disable-next-line no-param-reassign
    element = element.parentNode;
  }

  return (_element = element) == null ? void 0 : _element.dir;
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (getTextDirection);

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/getTimeStamp.js":
/*!**********************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/getTimeStamp.js ***!
  \**********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getTimeStamp)
/* harmony export */ });
/**
 * Adds zero to strings shorter than two characters.
 *
 * @param {number} number
 * @returns {string}
 */
function pad(number) {
  return number < 10 ? `0${number}` : number.toString();
}
/**
 * Returns a timestamp in the format of `hours:minutes:seconds`
 */


function getTimeStamp() {
  const date = new Date();
  const hours = pad(date.getHours());
  const minutes = pad(date.getMinutes());
  const seconds = pad(date.getSeconds());
  return `${hours}:${minutes}:${seconds}`;
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/hasProperty.js":
/*!*********************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/hasProperty.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ has)
/* harmony export */ });
function has(object, key) {
  return Object.prototype.hasOwnProperty.call(object, key);
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/isDOMElement.js":
/*!**********************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/isDOMElement.js ***!
  \**********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ isDOMElement)
/* harmony export */ });
/**
 * Check if an object is a DOM element. Duck-typing based on `nodeType`.
 *
 * @param {*} obj
 */
function isDOMElement(obj) {
  return (obj == null ? void 0 : obj.nodeType) === Node.ELEMENT_NODE;
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/isDragDropSupported.js":
/*!*****************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/isDragDropSupported.js ***!
  \*****************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ isDragDropSupported)
/* harmony export */ });
/**
 * Checks if the browser supports Drag & Drop (not supported on mobile devices, for example).
 *
 * @returns {boolean}
 */
function isDragDropSupported() {
  const div = document.body;

  if (!('draggable' in div) || !('ondragstart' in div && 'ondrop' in div)) {
    return false;
  }

  if (!('FormData' in window)) {
    return false;
  }

  if (!('FileReader' in window)) {
    return false;
  }

  return true;
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/isObjectURL.js":
/*!*********************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/isObjectURL.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ isObjectURL)
/* harmony export */ });
/**
 * Check if a URL string is an object URL from `URL.createObjectURL`.
 *
 * @param {string} url
 * @returns {boolean}
 */
function isObjectURL(url) {
  return url.startsWith('blob:');
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/isPreviewSupported.js":
/*!****************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/isPreviewSupported.js ***!
  \****************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ isPreviewSupported)
/* harmony export */ });
function isPreviewSupported(fileType) {
  if (!fileType) return false; // list of images that browsers can preview

  return /^[^/]+\/(jpe?g|gif|png|svg|svg\+xml|bmp|webp|avif)$/.test(fileType);
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/mimeTypes.js":
/*!*******************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/mimeTypes.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
// ___Why not add the mime-types package?
//    It's 19.7kB gzipped, and we only need mime types for well-known extensions (for file previews).
// ___Where to take new extensions from?
//    https://github.com/jshttp/mime-db/blob/master/db.json
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  md: 'text/markdown',
  markdown: 'text/markdown',
  mp4: 'video/mp4',
  mp3: 'audio/mp3',
  svg: 'image/svg+xml',
  jpg: 'image/jpeg',
  png: 'image/png',
  webp: 'image/webp',
  gif: 'image/gif',
  heic: 'image/heic',
  heif: 'image/heif',
  yaml: 'text/yaml',
  yml: 'text/yaml',
  csv: 'text/csv',
  tsv: 'text/tab-separated-values',
  tab: 'text/tab-separated-values',
  avi: 'video/x-msvideo',
  mks: 'video/x-matroska',
  mkv: 'video/x-matroska',
  mov: 'video/quicktime',
  dicom: 'application/dicom',
  doc: 'application/msword',
  docm: 'application/vnd.ms-word.document.macroenabled.12',
  docx: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  dot: 'application/msword',
  dotm: 'application/vnd.ms-word.template.macroenabled.12',
  dotx: 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
  xla: 'application/vnd.ms-excel',
  xlam: 'application/vnd.ms-excel.addin.macroenabled.12',
  xlc: 'application/vnd.ms-excel',
  xlf: 'application/x-xliff+xml',
  xlm: 'application/vnd.ms-excel',
  xls: 'application/vnd.ms-excel',
  xlsb: 'application/vnd.ms-excel.sheet.binary.macroenabled.12',
  xlsm: 'application/vnd.ms-excel.sheet.macroenabled.12',
  xlsx: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  xlt: 'application/vnd.ms-excel',
  xltm: 'application/vnd.ms-excel.template.macroenabled.12',
  xltx: 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
  xlw: 'application/vnd.ms-excel',
  txt: 'text/plain',
  text: 'text/plain',
  conf: 'text/plain',
  log: 'text/plain',
  pdf: 'application/pdf',
  zip: 'application/zip',
  '7z': 'application/x-7z-compressed',
  rar: 'application/x-rar-compressed',
  tar: 'application/x-tar',
  gz: 'application/gzip',
  dmg: 'application/x-apple-diskimage'
});

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/prettyETA.js":
/*!*******************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/prettyETA.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ prettyETA)
/* harmony export */ });
/* harmony import */ var _secondsToTime_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./secondsToTime.js */ "./src/jgDashboard/node_modules/@uppy/utils/lib/secondsToTime.js");

function prettyETA(seconds) {
  const time = (0,_secondsToTime_js__WEBPACK_IMPORTED_MODULE_0__["default"])(seconds); // Only display hours and minutes if they are greater than 0 but always
  // display minutes if hours is being displayed
  // Display a leading zero if the there is a preceding unit: 1m 05s, but 5s

  const hoursStr = time.hours === 0 ? '' : `${time.hours}h`;
  const minutesStr = time.minutes === 0 ? '' : `${time.hours === 0 ? time.minutes : ` ${time.minutes.toString(10).padStart(2, '0')}`}m`;
  const secondsStr = time.hours !== 0 ? '' : `${time.minutes === 0 ? time.seconds : ` ${time.seconds.toString(10).padStart(2, '0')}`}s`;
  return `${hoursStr}${minutesStr}${secondsStr}`;
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/secondsToTime.js":
/*!***********************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/secondsToTime.js ***!
  \***********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ secondsToTime)
/* harmony export */ });
function secondsToTime(rawSeconds) {
  const hours = Math.floor(rawSeconds / 3600) % 24;
  const minutes = Math.floor(rawSeconds / 60) % 60;
  const seconds = Math.floor(rawSeconds % 60);
  return {
    hours,
    minutes,
    seconds
  };
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/toArray.js":
/*!*****************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/toArray.js ***!
  \*****************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * Converts list into array
 */
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Array.from);

/***/ }),

/***/ "./src/jgDashboard/node_modules/@uppy/utils/lib/truncateString.js":
/*!************************************************************************!*\
  !*** ./src/jgDashboard/node_modules/@uppy/utils/lib/truncateString.js ***!
  \************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ truncateString)
/* harmony export */ });
/**
 * Truncates a string to the given number of chars (maxLength) by inserting '...' in the middle of that string.
 * Partially taken from https://stackoverflow.com/a/5723274/3192470.
 *
 * @param {string} string - string to be truncated
 * @param {number} maxLength - maximum size of the resulting string
 * @returns {string}
 */
const separator = '...';
function truncateString(string, maxLength) {
  // Return the empty string if maxLength is zero
  if (maxLength === 0) return ''; // Return original string if it's already shorter than maxLength

  if (string.length <= maxLength) return string; // Return truncated substring appended of the ellipsis char if string can't be meaningfully truncated

  if (maxLength <= separator.length + 1) return `${string.slice(0, maxLength - 1)}…`;
  const charsToShow = maxLength - separator.length;
  const frontChars = Math.ceil(charsToShow / 2);
  const backChars = Math.floor(charsToShow / 2);
  return string.slice(0, frontChars) + separator + string.slice(-backChars);
}

/***/ }),

/***/ "./src/jgDashboard/node_modules/exifr/dist/mini.esm.mjs":
/*!**************************************************************!*\
  !*** ./src/jgDashboard/node_modules/exifr/dist/mini.esm.mjs ***!
  \**************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Exifr: () => (/* binding */ H),
/* harmony export */   Options: () => (/* binding */ R),
/* harmony export */   allFormatters: () => (/* binding */ M),
/* harmony export */   chunkedProps: () => (/* binding */ L),
/* harmony export */   createDictionary: () => (/* binding */ x),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   extendDictionary: () => (/* binding */ C),
/* harmony export */   fetchUrlAsArrayBuffer: () => (/* binding */ S),
/* harmony export */   fileParsers: () => (/* binding */ m),
/* harmony export */   fileReaders: () => (/* binding */ b),
/* harmony export */   gps: () => (/* binding */ fe),
/* harmony export */   gpsOnlyOptions: () => (/* binding */ he),
/* harmony export */   inheritables: () => (/* binding */ E),
/* harmony export */   orientation: () => (/* binding */ ce),
/* harmony export */   orientationOnlyOptions: () => (/* binding */ de),
/* harmony export */   otherSegments: () => (/* binding */ T),
/* harmony export */   parse: () => (/* binding */ Y),
/* harmony export */   readBlobAsArrayBuffer: () => (/* binding */ A),
/* harmony export */   rotateCanvas: () => (/* binding */ ge),
/* harmony export */   rotateCss: () => (/* binding */ me),
/* harmony export */   rotation: () => (/* binding */ ye),
/* harmony export */   rotations: () => (/* binding */ pe),
/* harmony export */   segmentParsers: () => (/* binding */ y),
/* harmony export */   segments: () => (/* binding */ z),
/* harmony export */   segmentsAndBlocks: () => (/* binding */ F),
/* harmony export */   tagKeys: () => (/* binding */ B),
/* harmony export */   tagRevivers: () => (/* binding */ I),
/* harmony export */   tagValues: () => (/* binding */ V),
/* harmony export */   thumbnail: () => (/* binding */ oe),
/* harmony export */   thumbnailOnlyOptions: () => (/* binding */ le),
/* harmony export */   thumbnailUrl: () => (/* binding */ ue),
/* harmony export */   tiffBlocks: () => (/* binding */ P),
/* harmony export */   tiffExtractables: () => (/* binding */ j)
/* harmony export */ });
function e(e,t,s){return t in e?Object.defineProperty(e,t,{value:s,enumerable:!0,configurable:!0,writable:!0}):e[t]=s,e}var t="undefined"!=typeof self?self:global;const s="undefined"!=typeof navigator,i=s&&"undefined"==typeof HTMLImageElement,n=!("undefined"==typeof global||"undefined"==typeof process||!process.versions||!process.versions.node),r=t.Buffer,a=!!r,h=e=>void 0!==e;function f(e){return void 0===e||(e instanceof Map?0===e.size:0===Object.values(e).filter(h).length)}function l(e){let t=new Error(e);throw delete t.stack,t}function o(e){let t=function(e){let t=0;return e.ifd0.enabled&&(t+=1024),e.exif.enabled&&(t+=2048),e.makerNote&&(t+=2048),e.userComment&&(t+=1024),e.gps.enabled&&(t+=512),e.interop.enabled&&(t+=100),e.ifd1.enabled&&(t+=1024),t+2048}(e);return e.jfif.enabled&&(t+=50),e.xmp.enabled&&(t+=2e4),e.iptc.enabled&&(t+=14e3),e.icc.enabled&&(t+=6e3),t}const u=e=>String.fromCharCode.apply(null,e),d="undefined"!=typeof TextDecoder?new TextDecoder("utf-8"):void 0;class c{static from(e,t){return e instanceof this&&e.le===t?e:new c(e,void 0,void 0,t)}constructor(e,t=0,s,i){if("boolean"==typeof i&&(this.le=i),Array.isArray(e)&&(e=new Uint8Array(e)),0===e)this.byteOffset=0,this.byteLength=0;else if(e instanceof ArrayBuffer){void 0===s&&(s=e.byteLength-t);let i=new DataView(e,t,s);this._swapDataView(i)}else if(e instanceof Uint8Array||e instanceof DataView||e instanceof c){void 0===s&&(s=e.byteLength-t),(t+=e.byteOffset)+s>e.byteOffset+e.byteLength&&l("Creating view outside of available memory in ArrayBuffer");let i=new DataView(e.buffer,t,s);this._swapDataView(i)}else if("number"==typeof e){let t=new DataView(new ArrayBuffer(e));this._swapDataView(t)}else l("Invalid input argument for BufferView: "+e)}_swapArrayBuffer(e){this._swapDataView(new DataView(e))}_swapBuffer(e){this._swapDataView(new DataView(e.buffer,e.byteOffset,e.byteLength))}_swapDataView(e){this.dataView=e,this.buffer=e.buffer,this.byteOffset=e.byteOffset,this.byteLength=e.byteLength}_lengthToEnd(e){return this.byteLength-e}set(e,t,s=c){return e instanceof DataView||e instanceof c?e=new Uint8Array(e.buffer,e.byteOffset,e.byteLength):e instanceof ArrayBuffer&&(e=new Uint8Array(e)),e instanceof Uint8Array||l("BufferView.set(): Invalid data argument."),this.toUint8().set(e,t),new s(this,t,e.byteLength)}subarray(e,t){return t=t||this._lengthToEnd(e),new c(this,e,t)}toUint8(){return new Uint8Array(this.buffer,this.byteOffset,this.byteLength)}getUint8Array(e,t){return new Uint8Array(this.buffer,this.byteOffset+e,t)}getString(e=0,t=this.byteLength){let s=this.getUint8Array(e,t);return i=s,d?d.decode(i):a?Buffer.from(i).toString("utf8"):decodeURIComponent(escape(u(i)));var i}getLatin1String(e=0,t=this.byteLength){let s=this.getUint8Array(e,t);return u(s)}getUnicodeString(e=0,t=this.byteLength){const s=[];for(let i=0;i<t&&e+i<this.byteLength;i+=2)s.push(this.getUint16(e+i));return u(s)}getInt8(e){return this.dataView.getInt8(e)}getUint8(e){return this.dataView.getUint8(e)}getInt16(e,t=this.le){return this.dataView.getInt16(e,t)}getInt32(e,t=this.le){return this.dataView.getInt32(e,t)}getUint16(e,t=this.le){return this.dataView.getUint16(e,t)}getUint32(e,t=this.le){return this.dataView.getUint32(e,t)}getFloat32(e,t=this.le){return this.dataView.getFloat32(e,t)}getFloat64(e,t=this.le){return this.dataView.getFloat64(e,t)}getFloat(e,t=this.le){return this.dataView.getFloat32(e,t)}getDouble(e,t=this.le){return this.dataView.getFloat64(e,t)}getUintBytes(e,t,s){switch(t){case 1:return this.getUint8(e,s);case 2:return this.getUint16(e,s);case 4:return this.getUint32(e,s);case 8:return this.getUint64&&this.getUint64(e,s)}}getUint(e,t,s){switch(t){case 8:return this.getUint8(e,s);case 16:return this.getUint16(e,s);case 32:return this.getUint32(e,s);case 64:return this.getUint64&&this.getUint64(e,s)}}toString(e){return this.dataView.toString(e,this.constructor.name)}ensureChunk(){}}function p(e,t){l(`${e} '${t}' was not loaded, try using full build of exifr.`)}class g extends Map{constructor(e){super(),this.kind=e}get(e,t){return this.has(e)||p(this.kind,e),t&&(e in t||function(e,t){l(`Unknown ${e} '${t}'.`)}(this.kind,e),t[e].enabled||p(this.kind,e)),super.get(e)}keyList(){return Array.from(this.keys())}}var m=new g("file parser"),y=new g("segment parser"),b=new g("file reader");let w=t.fetch;function k(e,t){return(i=e).startsWith("data:")||i.length>1e4?v(e,t,"base64"):n&&e.includes("://")?O(e,t,"url",S):n?v(e,t,"fs"):s?O(e,t,"url",S):void l("Invalid input argument");var i}async function O(e,t,s,i){return b.has(s)?v(e,t,s):i?async function(e,t){let s=await t(e);return new c(s)}(e,i):void l(`Parser ${s} is not loaded`)}async function v(e,t,s){let i=new(b.get(s))(e,t);return await i.read(),i}const S=e=>w(e).then((e=>e.arrayBuffer())),A=e=>new Promise(((t,s)=>{let i=new FileReader;i.onloadend=()=>t(i.result||new ArrayBuffer),i.onerror=s,i.readAsArrayBuffer(e)}));class U extends Map{get tagKeys(){return this.allKeys||(this.allKeys=Array.from(this.keys())),this.allKeys}get tagValues(){return this.allValues||(this.allValues=Array.from(this.values())),this.allValues}}function x(e,t,s){let i=new U;for(let[e,t]of s)i.set(e,t);if(Array.isArray(t))for(let s of t)e.set(s,i);else e.set(t,i);return i}function C(e,t,s){let i,n=e.get(t);for(i of s)n.set(i[0],i[1])}const B=new Map,V=new Map,I=new Map,L=["chunked","firstChunkSize","firstChunkSizeNode","firstChunkSizeBrowser","chunkSize","chunkLimit"],T=["jfif","xmp","icc","iptc","ihdr"],z=["tiff",...T],P=["ifd0","ifd1","exif","gps","interop"],F=[...z,...P],j=["makerNote","userComment"],E=["translateKeys","translateValues","reviveValues","multiSegment"],M=[...E,"sanitize","mergeOutput","silentErrors"];class _{get translate(){return this.translateKeys||this.translateValues||this.reviveValues}}class D extends _{get needed(){return this.enabled||this.deps.size>0}constructor(t,s,i,n){if(super(),e(this,"enabled",!1),e(this,"skip",new Set),e(this,"pick",new Set),e(this,"deps",new Set),e(this,"translateKeys",!1),e(this,"translateValues",!1),e(this,"reviveValues",!1),this.key=t,this.enabled=s,this.parse=this.enabled,this.applyInheritables(n),this.canBeFiltered=P.includes(t),this.canBeFiltered&&(this.dict=B.get(t)),void 0!==i)if(Array.isArray(i))this.parse=this.enabled=!0,this.canBeFiltered&&i.length>0&&this.translateTagSet(i,this.pick);else if("object"==typeof i){if(this.enabled=!0,this.parse=!1!==i.parse,this.canBeFiltered){let{pick:e,skip:t}=i;e&&e.length>0&&this.translateTagSet(e,this.pick),t&&t.length>0&&this.translateTagSet(t,this.skip)}this.applyInheritables(i)}else!0===i||!1===i?this.parse=this.enabled=i:l(`Invalid options argument: ${i}`)}applyInheritables(e){let t,s;for(t of E)s=e[t],void 0!==s&&(this[t]=s)}translateTagSet(e,t){if(this.dict){let s,i,{tagKeys:n,tagValues:r}=this.dict;for(s of e)"string"==typeof s?(i=r.indexOf(s),-1===i&&(i=n.indexOf(Number(s))),-1!==i&&t.add(Number(n[i]))):t.add(s)}else for(let s of e)t.add(s)}finalizeFilters(){!this.enabled&&this.deps.size>0?(this.enabled=!0,X(this.pick,this.deps)):this.enabled&&this.pick.size>0&&X(this.pick,this.deps)}}var N={jfif:!1,tiff:!0,xmp:!1,icc:!1,iptc:!1,ifd0:!0,ifd1:!1,exif:!0,gps:!0,interop:!1,ihdr:void 0,makerNote:!1,userComment:!1,multiSegment:!1,skip:[],pick:[],translateKeys:!0,translateValues:!0,reviveValues:!0,sanitize:!0,mergeOutput:!0,silentErrors:!0,chunked:!0,firstChunkSize:void 0,firstChunkSizeNode:512,firstChunkSizeBrowser:65536,chunkSize:65536,chunkLimit:5},$=new Map;class R extends _{static useCached(e){let t=$.get(e);return void 0!==t||(t=new this(e),$.set(e,t)),t}constructor(e){super(),!0===e?this.setupFromTrue():void 0===e?this.setupFromUndefined():Array.isArray(e)?this.setupFromArray(e):"object"==typeof e?this.setupFromObject(e):l(`Invalid options argument ${e}`),void 0===this.firstChunkSize&&(this.firstChunkSize=s?this.firstChunkSizeBrowser:this.firstChunkSizeNode),this.mergeOutput&&(this.ifd1.enabled=!1),this.filterNestedSegmentTags(),this.traverseTiffDependencyTree(),this.checkLoadedPlugins()}setupFromUndefined(){let e;for(e of L)this[e]=N[e];for(e of M)this[e]=N[e];for(e of j)this[e]=N[e];for(e of F)this[e]=new D(e,N[e],void 0,this)}setupFromTrue(){let e;for(e of L)this[e]=N[e];for(e of M)this[e]=N[e];for(e of j)this[e]=!0;for(e of F)this[e]=new D(e,!0,void 0,this)}setupFromArray(e){let t;for(t of L)this[t]=N[t];for(t of M)this[t]=N[t];for(t of j)this[t]=N[t];for(t of F)this[t]=new D(t,!1,void 0,this);this.setupGlobalFilters(e,void 0,P)}setupFromObject(e){let t;for(t of(P.ifd0=P.ifd0||P.image,P.ifd1=P.ifd1||P.thumbnail,Object.assign(this,e),L))this[t]=W(e[t],N[t]);for(t of M)this[t]=W(e[t],N[t]);for(t of j)this[t]=W(e[t],N[t]);for(t of z)this[t]=new D(t,N[t],e[t],this);for(t of P)this[t]=new D(t,N[t],e[t],this.tiff);this.setupGlobalFilters(e.pick,e.skip,P,F),!0===e.tiff?this.batchEnableWithBool(P,!0):!1===e.tiff?this.batchEnableWithUserValue(P,e):Array.isArray(e.tiff)?this.setupGlobalFilters(e.tiff,void 0,P):"object"==typeof e.tiff&&this.setupGlobalFilters(e.tiff.pick,e.tiff.skip,P)}batchEnableWithBool(e,t){for(let s of e)this[s].enabled=t}batchEnableWithUserValue(e,t){for(let s of e){let e=t[s];this[s].enabled=!1!==e&&void 0!==e}}setupGlobalFilters(e,t,s,i=s){if(e&&e.length){for(let e of i)this[e].enabled=!1;let t=K(e,s);for(let[e,s]of t)X(this[e].pick,s),this[e].enabled=!0}else if(t&&t.length){let e=K(t,s);for(let[t,s]of e)X(this[t].skip,s)}}filterNestedSegmentTags(){let{ifd0:e,exif:t,xmp:s,iptc:i,icc:n}=this;this.makerNote?t.deps.add(37500):t.skip.add(37500),this.userComment?t.deps.add(37510):t.skip.add(37510),s.enabled||e.skip.add(700),i.enabled||e.skip.add(33723),n.enabled||e.skip.add(34675)}traverseTiffDependencyTree(){let{ifd0:e,exif:t,gps:s,interop:i}=this;i.needed&&(t.deps.add(40965),e.deps.add(40965)),t.needed&&e.deps.add(34665),s.needed&&e.deps.add(34853),this.tiff.enabled=P.some((e=>!0===this[e].enabled))||this.makerNote||this.userComment;for(let e of P)this[e].finalizeFilters()}get onlyTiff(){return!T.map((e=>this[e].enabled)).some((e=>!0===e))&&this.tiff.enabled}checkLoadedPlugins(){for(let e of z)this[e].enabled&&!y.has(e)&&p("segment parser",e)}}function K(e,t){let s,i,n,r,a=[];for(n of t){for(r of(s=B.get(n),i=[],s))(e.includes(r[0])||e.includes(r[1]))&&i.push(r[0]);i.length&&a.push([n,i])}return a}function W(e,t){return void 0!==e?e:void 0!==t?t:void 0}function X(e,t){for(let s of t)e.add(s)}e(R,"default",N);class H{constructor(t){e(this,"parsers",{}),e(this,"output",{}),e(this,"errors",[]),e(this,"pushToErrors",(e=>this.errors.push(e))),this.options=R.useCached(t)}async read(e){this.file=await function(e,t){return"string"==typeof e?k(e,t):s&&!i&&e instanceof HTMLImageElement?k(e.src,t):e instanceof Uint8Array||e instanceof ArrayBuffer||e instanceof DataView?new c(e):s&&e instanceof Blob?O(e,t,"blob",A):void l("Invalid input argument")}(e,this.options)}setup(){if(this.fileParser)return;let{file:e}=this,t=e.getUint16(0);for(let[s,i]of m)if(i.canHandle(e,t))return this.fileParser=new i(this.options,this.file,this.parsers),e[s]=!0;this.file.close&&this.file.close(),l("Unknown file format")}async parse(){let{output:e,errors:t}=this;return this.setup(),this.options.silentErrors?(await this.executeParsers().catch(this.pushToErrors),t.push(...this.fileParser.errors)):await this.executeParsers(),this.file.close&&this.file.close(),this.options.silentErrors&&t.length>0&&(e.errors=t),f(s=e)?void 0:s;var s}async executeParsers(){let{output:e}=this;await this.fileParser.parse();let t=Object.values(this.parsers).map((async t=>{let s=await t.parse();t.assignToOutput(e,s)}));this.options.silentErrors&&(t=t.map((e=>e.catch(this.pushToErrors)))),await Promise.all(t)}async extractThumbnail(){this.setup();let{options:e,file:t}=this,s=y.get("tiff",e);var i;if(t.tiff?i={start:0,type:"tiff"}:t.jpeg&&(i=await this.fileParser.getOrFindSegment("tiff")),void 0===i)return;let n=await this.fileParser.ensureSegmentChunk(i),r=this.parsers.tiff=new s(n,e,t),a=await r.extractThumbnail();return t.close&&t.close(),a}}async function Y(e,t){let s=new H(t);return await s.read(e),s.parse()}var G=Object.freeze({__proto__:null,parse:Y,Exifr:H,fileParsers:m,segmentParsers:y,fileReaders:b,tagKeys:B,tagValues:V,tagRevivers:I,createDictionary:x,extendDictionary:C,fetchUrlAsArrayBuffer:S,readBlobAsArrayBuffer:A,chunkedProps:L,otherSegments:T,segments:z,tiffBlocks:P,segmentsAndBlocks:F,tiffExtractables:j,inheritables:E,allFormatters:M,Options:R});class J{static findPosition(e,t){let s=e.getUint16(t+2)+2,i="function"==typeof this.headerLength?this.headerLength(e,t,s):this.headerLength,n=t+i,r=s-i;return{offset:t,length:s,headerLength:i,start:n,size:r,end:n+r}}static parse(e,t={}){return new this(e,new R({[this.type]:t}),e).parse()}normalizeInput(e){return e instanceof c?e:new c(e)}constructor(t,s={},i){e(this,"errors",[]),e(this,"raw",new Map),e(this,"handleError",(e=>{if(!this.options.silentErrors)throw e;this.errors.push(e.message)})),this.chunk=this.normalizeInput(t),this.file=i,this.type=this.constructor.type,this.globalOptions=this.options=s,this.localOptions=s[this.type],this.canTranslate=this.localOptions&&this.localOptions.translate}translate(){this.canTranslate&&(this.translated=this.translateBlock(this.raw,this.type))}get output(){return this.translated?this.translated:this.raw?Object.fromEntries(this.raw):void 0}translateBlock(e,t){let s=I.get(t),i=V.get(t),n=B.get(t),r=this.options[t],a=r.reviveValues&&!!s,h=r.translateValues&&!!i,f=r.translateKeys&&!!n,l={};for(let[t,r]of e)a&&s.has(t)?r=s.get(t)(r):h&&i.has(t)&&(r=this.translateValue(r,i.get(t))),f&&n.has(t)&&(t=n.get(t)||t),l[t]=r;return l}translateValue(e,t){return t[e]||t.DEFAULT||e}assignToOutput(e,t){this.assignObjectToOutput(e,this.constructor.type,t)}assignObjectToOutput(e,t,s){if(this.globalOptions.mergeOutput)return Object.assign(e,s);e[t]?Object.assign(e[t],s):e[t]=s}}e(J,"headerLength",4),e(J,"type",void 0),e(J,"multiSegment",!1),e(J,"canHandle",(()=>!1));function q(e){return 192===e||194===e||196===e||219===e||221===e||218===e||254===e}function Q(e){return e>=224&&e<=239}function Z(e,t,s){for(let[i,n]of y)if(n.canHandle(e,t,s))return i}class ee extends class{constructor(t,s,i){e(this,"errors",[]),e(this,"ensureSegmentChunk",(async e=>{let t=e.start,s=e.size||65536;if(this.file.chunked)if(this.file.available(t,s))e.chunk=this.file.subarray(t,s);else try{e.chunk=await this.file.readChunk(t,s)}catch(t){l(`Couldn't read segment: ${JSON.stringify(e)}. ${t.message}`)}else this.file.byteLength>t+s?e.chunk=this.file.subarray(t,s):void 0===e.size?e.chunk=this.file.subarray(t):l("Segment unreachable: "+JSON.stringify(e));return e.chunk})),this.extendOptions&&this.extendOptions(t),this.options=t,this.file=s,this.parsers=i}injectSegment(e,t){this.options[e].enabled&&this.createParser(e,t)}createParser(e,t){let s=new(y.get(e))(t,this.options,this.file);return this.parsers[e]=s}createParsers(e){for(let t of e){let{type:e,chunk:s}=t,i=this.options[e];if(i&&i.enabled){let t=this.parsers[e];t&&t.append||t||this.createParser(e,s)}}}async readSegments(e){let t=e.map(this.ensureSegmentChunk);await Promise.all(t)}}{constructor(...t){super(...t),e(this,"appSegments",[]),e(this,"jpegSegments",[]),e(this,"unknownSegments",[])}static canHandle(e,t){return 65496===t}async parse(){await this.findAppSegments(),await this.readSegments(this.appSegments),this.mergeMultiSegments(),this.createParsers(this.mergedAppSegments||this.appSegments)}setupSegmentFinderArgs(e){!0===e?(this.findAll=!0,this.wanted=new Set(y.keyList())):(e=void 0===e?y.keyList().filter((e=>this.options[e].enabled)):e.filter((e=>this.options[e].enabled&&y.has(e))),this.findAll=!1,this.remaining=new Set(e),this.wanted=new Set(e)),this.unfinishedMultiSegment=!1}async findAppSegments(e=0,t){this.setupSegmentFinderArgs(t);let{file:s,findAll:i,wanted:n,remaining:r}=this;if(!i&&this.file.chunked&&(i=Array.from(n).some((e=>{let t=y.get(e),s=this.options[e];return t.multiSegment&&s.multiSegment})),i&&await this.file.readWhole()),e=this.findAppSegmentsInRange(e,s.byteLength),!this.options.onlyTiff&&s.chunked){let t=!1;for(;r.size>0&&!t&&(s.canReadNextChunk||this.unfinishedMultiSegment);){let{nextChunkOffset:i}=s,n=this.appSegments.some((e=>!this.file.available(e.offset||e.start,e.length||e.size)));if(t=e>i&&!n?!await s.readNextChunk(e):!await s.readNextChunk(i),void 0===(e=this.findAppSegmentsInRange(e,s.byteLength)))return}}}findAppSegmentsInRange(e,t){t-=2;let s,i,n,r,a,h,{file:f,findAll:l,wanted:o,remaining:u,options:d}=this;for(;e<t;e++)if(255===f.getUint8(e))if(s=f.getUint8(e+1),Q(s)){if(i=f.getUint16(e+2),n=Z(f,e,i),n&&o.has(n)&&(r=y.get(n),a=r.findPosition(f,e),h=d[n],a.type=n,this.appSegments.push(a),!l&&(r.multiSegment&&h.multiSegment?(this.unfinishedMultiSegment=a.chunkNumber<a.chunkCount,this.unfinishedMultiSegment||u.delete(n)):u.delete(n),0===u.size)))break;d.recordUnknownSegments&&(a=J.findPosition(f,e),a.marker=s,this.unknownSegments.push(a)),e+=i+1}else if(q(s)){if(i=f.getUint16(e+2),218===s&&!1!==d.stopAfterSos)return;d.recordJpegSegments&&this.jpegSegments.push({offset:e,length:i,marker:s}),e+=i+1}return e}mergeMultiSegments(){if(!this.appSegments.some((e=>e.multiSegment)))return;let e=function(e,t){let s,i,n,r=new Map;for(let a=0;a<e.length;a++)s=e[a],i=s[t],r.has(i)?n=r.get(i):r.set(i,n=[]),n.push(s);return Array.from(r)}(this.appSegments,"type");this.mergedAppSegments=e.map((([e,t])=>{let s=y.get(e,this.options);if(s.handleMultiSegments){return{type:e,chunk:s.handleMultiSegments(t)}}return t[0]}))}getSegment(e){return this.appSegments.find((t=>t.type===e))}async getOrFindSegment(e){let t=this.getSegment(e);return void 0===t&&(await this.findAppSegments(0,[e]),t=this.getSegment(e)),t}}e(ee,"type","jpeg"),m.set("jpeg",ee);const te=[void 0,1,1,2,4,8,1,1,2,4,8,4,8,4];class se extends J{parseHeader(){var e=this.chunk.getUint16();18761===e?this.le=!0:19789===e&&(this.le=!1),this.chunk.le=this.le,this.headerParsed=!0}parseTags(e,t,s=new Map){let{pick:i,skip:n}=this.options[t];i=new Set(i);let r=i.size>0,a=0===n.size,h=this.chunk.getUint16(e);e+=2;for(let f=0;f<h;f++){let h=this.chunk.getUint16(e);if(r){if(i.has(h)&&(s.set(h,this.parseTag(e,h,t)),i.delete(h),0===i.size))break}else!a&&n.has(h)||s.set(h,this.parseTag(e,h,t));e+=12}return s}parseTag(e,t,s){let{chunk:i}=this,n=i.getUint16(e+2),r=i.getUint32(e+4),a=te[n];if(a*r<=4?e+=8:e=i.getUint32(e+8),(n<1||n>13)&&l(`Invalid TIFF value type. block: ${s.toUpperCase()}, tag: ${t.toString(16)}, type: ${n}, offset ${e}`),e>i.byteLength&&l(`Invalid TIFF value offset. block: ${s.toUpperCase()}, tag: ${t.toString(16)}, type: ${n}, offset ${e} is outside of chunk size ${i.byteLength}`),1===n)return i.getUint8Array(e,r);if(2===n)return""===(h=function(e){for(;e.endsWith("\0");)e=e.slice(0,-1);return e}(h=i.getString(e,r)).trim())?void 0:h;var h;if(7===n)return i.getUint8Array(e,r);if(1===r)return this.parseTagValue(n,e);{let t=new(function(e){switch(e){case 1:return Uint8Array;case 3:return Uint16Array;case 4:return Uint32Array;case 5:return Array;case 6:return Int8Array;case 8:return Int16Array;case 9:return Int32Array;case 10:return Array;case 11:return Float32Array;case 12:return Float64Array;default:return Array}}(n))(r),s=a;for(let i=0;i<r;i++)t[i]=this.parseTagValue(n,e),e+=s;return t}}parseTagValue(e,t){let{chunk:s}=this;switch(e){case 1:return s.getUint8(t);case 3:return s.getUint16(t);case 4:return s.getUint32(t);case 5:return s.getUint32(t)/s.getUint32(t+4);case 6:return s.getInt8(t);case 8:return s.getInt16(t);case 9:return s.getInt32(t);case 10:return s.getInt32(t)/s.getInt32(t+4);case 11:return s.getFloat(t);case 12:return s.getDouble(t);case 13:return s.getUint32(t);default:l(`Invalid tiff type ${e}`)}}}class ie extends se{static canHandle(e,t){return 225===e.getUint8(t+1)&&1165519206===e.getUint32(t+4)&&0===e.getUint16(t+8)}async parse(){this.parseHeader();let{options:e}=this;return e.ifd0.enabled&&await this.parseIfd0Block(),e.exif.enabled&&await this.safeParse("parseExifBlock"),e.gps.enabled&&await this.safeParse("parseGpsBlock"),e.interop.enabled&&await this.safeParse("parseInteropBlock"),e.ifd1.enabled&&await this.safeParse("parseThumbnailBlock"),this.createOutput()}safeParse(e){let t=this[e]();return void 0!==t.catch&&(t=t.catch(this.handleError)),t}findIfd0Offset(){void 0===this.ifd0Offset&&(this.ifd0Offset=this.chunk.getUint32(4))}findIfd1Offset(){if(void 0===this.ifd1Offset){this.findIfd0Offset();let e=this.chunk.getUint16(this.ifd0Offset),t=this.ifd0Offset+2+12*e;this.ifd1Offset=this.chunk.getUint32(t)}}parseBlock(e,t){let s=new Map;return this[t]=s,this.parseTags(e,t,s),s}async parseIfd0Block(){if(this.ifd0)return;let{file:e}=this;this.findIfd0Offset(),this.ifd0Offset<8&&l("Malformed EXIF data"),!e.chunked&&this.ifd0Offset>e.byteLength&&l(`IFD0 offset points to outside of file.\nthis.ifd0Offset: ${this.ifd0Offset}, file.byteLength: ${e.byteLength}`),e.tiff&&await e.ensureChunk(this.ifd0Offset,o(this.options));let t=this.parseBlock(this.ifd0Offset,"ifd0");return 0!==t.size?(this.exifOffset=t.get(34665),this.interopOffset=t.get(40965),this.gpsOffset=t.get(34853),this.xmp=t.get(700),this.iptc=t.get(33723),this.icc=t.get(34675),this.options.sanitize&&(t.delete(34665),t.delete(40965),t.delete(34853),t.delete(700),t.delete(33723),t.delete(34675)),t):void 0}async parseExifBlock(){if(this.exif)return;if(this.ifd0||await this.parseIfd0Block(),void 0===this.exifOffset)return;this.file.tiff&&await this.file.ensureChunk(this.exifOffset,o(this.options));let e=this.parseBlock(this.exifOffset,"exif");return this.interopOffset||(this.interopOffset=e.get(40965)),this.makerNote=e.get(37500),this.userComment=e.get(37510),this.options.sanitize&&(e.delete(40965),e.delete(37500),e.delete(37510)),this.unpack(e,41728),this.unpack(e,41729),e}unpack(e,t){let s=e.get(t);s&&1===s.length&&e.set(t,s[0])}async parseGpsBlock(){if(this.gps)return;if(this.ifd0||await this.parseIfd0Block(),void 0===this.gpsOffset)return;let e=this.parseBlock(this.gpsOffset,"gps");return e&&e.has(2)&&e.has(4)&&(e.set("latitude",ne(...e.get(2),e.get(1))),e.set("longitude",ne(...e.get(4),e.get(3)))),e}async parseInteropBlock(){if(!this.interop&&(this.ifd0||await this.parseIfd0Block(),void 0!==this.interopOffset||this.exif||await this.parseExifBlock(),void 0!==this.interopOffset))return this.parseBlock(this.interopOffset,"interop")}async parseThumbnailBlock(e=!1){if(!this.ifd1&&!this.ifd1Parsed&&(!this.options.mergeOutput||e))return this.findIfd1Offset(),this.ifd1Offset>0&&(this.parseBlock(this.ifd1Offset,"ifd1"),this.ifd1Parsed=!0),this.ifd1}async extractThumbnail(){if(this.headerParsed||this.parseHeader(),this.ifd1Parsed||await this.parseThumbnailBlock(!0),void 0===this.ifd1)return;let e=this.ifd1.get(513),t=this.ifd1.get(514);return this.chunk.getUint8Array(e,t)}get image(){return this.ifd0}get thumbnail(){return this.ifd1}createOutput(){let e,t,s,i={};for(t of P)if(e=this[t],!f(e))if(s=this.canTranslate?this.translateBlock(e,t):Object.fromEntries(e),this.options.mergeOutput){if("ifd1"===t)continue;Object.assign(i,s)}else i[t]=s;return this.makerNote&&(i.makerNote=this.makerNote),this.userComment&&(i.userComment=this.userComment),i}assignToOutput(e,t){if(this.globalOptions.mergeOutput)Object.assign(e,t);else for(let[s,i]of Object.entries(t))this.assignObjectToOutput(e,s,i)}}function ne(e,t,s,i){var n=e+t/60+s/3600;return"S"!==i&&"W"!==i||(n*=-1),n}e(ie,"type","tiff"),e(ie,"headerLength",10),y.set("tiff",ie);var re=Object.freeze({__proto__:null,default:G,Exifr:H,fileParsers:m,segmentParsers:y,fileReaders:b,tagKeys:B,tagValues:V,tagRevivers:I,createDictionary:x,extendDictionary:C,fetchUrlAsArrayBuffer:S,readBlobAsArrayBuffer:A,chunkedProps:L,otherSegments:T,segments:z,tiffBlocks:P,segmentsAndBlocks:F,tiffExtractables:j,inheritables:E,allFormatters:M,Options:R,parse:Y});const ae={ifd0:!1,ifd1:!1,exif:!1,gps:!1,interop:!1,sanitize:!1,reviveValues:!0,translateKeys:!1,translateValues:!1,mergeOutput:!1},he=Object.assign({},ae,{firstChunkSize:4e4,gps:[1,2,3,4]});async function fe(e){let t=new H(he);await t.read(e);let s=await t.parse();if(s&&s.gps){let{latitude:e,longitude:t}=s.gps;return{latitude:e,longitude:t}}}const le=Object.assign({},ae,{tiff:!1,ifd1:!0,mergeOutput:!1});async function oe(e){let t=new H(le);await t.read(e);let s=await t.extractThumbnail();return s&&a?r.from(s):s}async function ue(e){let t=await this.thumbnail(e);if(void 0!==t){let e=new Blob([t]);return URL.createObjectURL(e)}}const de=Object.assign({},ae,{firstChunkSize:4e4,ifd0:[274]});async function ce(e){let t=new H(de);await t.read(e);let s=await t.parse();if(s&&s.ifd0)return s.ifd0[274]}const pe=Object.freeze({1:{dimensionSwapped:!1,scaleX:1,scaleY:1,deg:0,rad:0},2:{dimensionSwapped:!1,scaleX:-1,scaleY:1,deg:0,rad:0},3:{dimensionSwapped:!1,scaleX:1,scaleY:1,deg:180,rad:180*Math.PI/180},4:{dimensionSwapped:!1,scaleX:-1,scaleY:1,deg:180,rad:180*Math.PI/180},5:{dimensionSwapped:!0,scaleX:1,scaleY:-1,deg:90,rad:90*Math.PI/180},6:{dimensionSwapped:!0,scaleX:1,scaleY:1,deg:90,rad:90*Math.PI/180},7:{dimensionSwapped:!0,scaleX:1,scaleY:-1,deg:270,rad:270*Math.PI/180},8:{dimensionSwapped:!0,scaleX:1,scaleY:1,deg:270,rad:270*Math.PI/180}});let ge=!0,me=!0;if("object"==typeof navigator){let e=navigator.userAgent;if(e.includes("iPad")||e.includes("iPhone")){let t=e.match(/OS (\d+)_(\d+)/);if(t){let[,e,s]=t,i=Number(e)+.1*Number(s);ge=i<13.4,me=!1}}else if(e.includes("OS X 10")){let[,t]=e.match(/OS X 10[_.](\d+)/);ge=me=Number(t)<15}if(e.includes("Chrome/")){let[,t]=e.match(/Chrome\/(\d+)/);ge=me=Number(t)<81}else if(e.includes("Firefox/")){let[,t]=e.match(/Firefox\/(\d+)/);ge=me=Number(t)<77}}async function ye(e){let t=await ce(e);return Object.assign({canvas:ge,css:me},pe[t])}class be extends c{constructor(...t){super(...t),e(this,"ranges",new we),0!==this.byteLength&&this.ranges.add(0,this.byteLength)}_tryExtend(e,t,s){if(0===e&&0===this.byteLength&&s){let e=new DataView(s.buffer||s,s.byteOffset,s.byteLength);this._swapDataView(e)}else{let s=e+t;if(s>this.byteLength){let{dataView:e}=this._extend(s);this._swapDataView(e)}}}_extend(e){let t;t=a?r.allocUnsafe(e):new Uint8Array(e);let s=new DataView(t.buffer,t.byteOffset,t.byteLength);return t.set(new Uint8Array(this.buffer,this.byteOffset,this.byteLength),0),{uintView:t,dataView:s}}subarray(e,t,s=!1){return t=t||this._lengthToEnd(e),s&&this._tryExtend(e,t),this.ranges.add(e,t),super.subarray(e,t)}set(e,t,s=!1){s&&this._tryExtend(t,e.byteLength,e);let i=super.set(e,t);return this.ranges.add(t,i.byteLength),i}async ensureChunk(e,t){this.chunked&&(this.ranges.available(e,t)||await this.readChunk(e,t))}available(e,t){return this.ranges.available(e,t)}}class we{constructor(){e(this,"list",[])}get length(){return this.list.length}add(e,t,s=0){let i=e+t,n=this.list.filter((t=>ke(e,t.offset,i)||ke(e,t.end,i)));if(n.length>0){e=Math.min(e,...n.map((e=>e.offset))),i=Math.max(i,...n.map((e=>e.end))),t=i-e;let s=n.shift();s.offset=e,s.length=t,s.end=i,this.list=this.list.filter((e=>!n.includes(e)))}else this.list.push({offset:e,length:t,end:i})}available(e,t){let s=e+t;return this.list.some((t=>t.offset<=e&&s<=t.end))}}function ke(e,t,s){return e<=t&&t<=s}class Oe extends be{constructor(t,s){super(0),e(this,"chunksRead",0),this.input=t,this.options=s}async readWhole(){this.chunked=!1,await this.readChunk(this.nextChunkOffset)}async readChunked(){this.chunked=!0,await this.readChunk(0,this.options.firstChunkSize)}async readNextChunk(e=this.nextChunkOffset){if(this.fullyRead)return this.chunksRead++,!1;let t=this.options.chunkSize,s=await this.readChunk(e,t);return!!s&&s.byteLength===t}async readChunk(e,t){if(this.chunksRead++,0!==(t=this.safeWrapAddress(e,t)))return this._readChunk(e,t)}safeWrapAddress(e,t){return void 0!==this.size&&e+t>this.size?Math.max(0,this.size-e):t}get nextChunkOffset(){if(0!==this.ranges.list.length)return this.ranges.list[0].length}get canReadNextChunk(){return this.chunksRead<this.options.chunkLimit}get fullyRead(){return void 0!==this.size&&this.nextChunkOffset===this.size}read(){return this.options.chunked?this.readChunked():this.readWhole()}close(){}}b.set("blob",class extends Oe{async readWhole(){this.chunked=!1;let e=await A(this.input);this._swapArrayBuffer(e)}readChunked(){return this.chunked=!0,this.size=this.input.size,super.readChunked()}async _readChunk(e,t){let s=t?e+t:void 0,i=this.input.slice(e,s),n=await A(i);return this.set(n,e,!0)}});/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (re);


/***/ }),

/***/ "./src/jgDashboard/node_modules/nanoid/non-secure/index.js":
/*!*****************************************************************!*\
  !*** ./src/jgDashboard/node_modules/nanoid/non-secure/index.js ***!
  \*****************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   customAlphabet: () => (/* binding */ customAlphabet),
/* harmony export */   nanoid: () => (/* binding */ nanoid)
/* harmony export */ });
let urlAlphabet =
  'useandom-26T198340PX75pxJACKVERYMINDBUSHWOLF_GQZbfghjklqvwyzrict'
let customAlphabet = (alphabet, defaultSize = 21) => {
  return (size = defaultSize) => {
    let id = ''
    let i = size
    while (i--) {
      id += alphabet[(Math.random() * alphabet.length) | 0]
    }
    return id
  }
}
let nanoid = (size = 21) => {
  let id = ''
  let i = size
  while (i--) {
    id += urlAlphabet[(Math.random() * 64) | 0]
  }
  return id
}


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/global */
/******/ 	(() => {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be in strict mode.
(() => {
"use strict";
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _uppy_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @uppy/core */ "./node_modules/@uppy/core/lib/index.js");
/* harmony import */ var _jgDashboard_lib_index_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./jgDashboard/lib/index.js */ "./src/jgDashboard/lib/index.js");
/* harmony import */ var _uppy_tus__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @uppy/tus */ "./node_modules/@uppy/tus/lib/index.js");
/* harmony import */ var _jgprocessor_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./jgprocessor.js */ "./src/jgprocessor.js");
// Script to handle tu uppy upload form


//import Dashboard from '@uppy/dashboard';



/**
 * Apply validity class to catid choices select field
 * 
 * @param  {Boolean}   ini      True to remove all validity classes
 */
function catidFieldValidity (ini = false) {
  let catid = document.getElementById('jform_catid');

  if(ini) {
    catid.parentElement.classList.remove('is-invalid');
    catid.parentElement.classList.remove('is-valid');

    return;
  }

  if(catid.checkValidity()) {
    // is-valid
    catid.parentElement.classList.remove('is-invalid');
    catid.parentElement.classList.add('is-valid');
  }
  else {
    // is-invalid
    catid.parentElement.classList.remove('is-valid');
    catid.parentElement.classList.add('is-invalid');
  }
}

var callback = function() {
  // document ready function

  // Initialize the form
  document.getElementById('adminForm').classList.remove('was-validated');
  catidFieldValidity(true);
  
  let uppy = new _uppy_core__WEBPACK_IMPORTED_MODULE_0__["default"]({
    autoProceed: false,
    onBeforeUpload: (files) => {return onBeforeUpload(files);},
    restrictions: {
      maxFileSize: window.uppyVars.maxFileSize,
      allowedFileTypes: window.uppyVars.allowedTypes,
    }
  });

  if(uppy != null)
  {
    document.getElementById('drag-drop-area').innerHTML = '';
  }

  uppy.use(_jgDashboard_lib_index_js__WEBPACK_IMPORTED_MODULE_1__["default"], {
    inline: true,
    target: window.uppyVars.uppyTarget,
    showProgressDetails: true,
    metaFields: [
      { id: 'jtitle', name: Joomla.JText._('JGLOBAL_TITLE'), placeholder: Joomla.JText._('COM_JOOMGALLERY_FILE_TITLE_HINT')},
      { id: 'jdescription', name: Joomla.JText._('JGLOBAL_DESCRIPTION'), placeholder: Joomla.JText._('COM_JOOMGALLERY_FILE_DESCRIPTION_HINT')},
      { id: 'jauthor', name: Joomla.JText._('JAUTHOR'), placeholder: Joomla.JText._('COM_JOOMGALLERY_FILE_AUTHOR_HINT')}
    ],
  });

  uppy.use(_uppy_tus__WEBPACK_IMPORTED_MODULE_2__["default"], {
    endpoint: window.uppyVars.TUSlocation,
    retryDelays: window.uppyVars.uppyDelays,
    allowedMetaFields: null,
    limit: window.uppyVars.uppyLimit
  });

  uppy.use(_jgprocessor_js__WEBPACK_IMPORTED_MODULE_3__["default"], {
    formID: 'adminForm',
    semaCalls: window.uppyVars.semaCalls,
    semaTokens: window.uppyVars.semaTokens
  });

  /**
   * Function called before upload is initiated
   * Doc: https://uppy.io/docs/uppy/#onbeforeuploadfiles
   *
   * @param   {Array}    files      List of files that will be uploaded
   *
   * @returns {Boolean}  True to continue the upload, false to cancel it
   */
  function onBeforeUpload(files) {
    // Initialize the form
    document.getElementById('adminForm').classList.remove('was-validated');
    document.getElementById('system-message-container').innerHTML = '';
    catidFieldValidity(true);

    // Check and validate the form
    let form = document.getElementById('adminForm');
    
    if(!form.checkValidity()) {
      // Form falidation failed
      // Cancel upload, render message
      Joomla.renderMessages({'error':[Joomla.JText._('JGLOBAL_VALIDATION_FORM_FAILED')+'. '+Joomla.JText._('COM_JOOMGALLERY_ERROR_FILL_REQUIRED_FIELDS')]});
      console.log(Joomla.JText._('JGLOBAL_VALIDATION_FORM_FAILED')+'. '+Joomla.JText._('COM_JOOMGALLERY_ERROR_FILL_REQUIRED_FIELDS'));
      form.classList.add('was-validated');
      catidFieldValidity();
      window.scrollTo(0, 0);

      return false;
    }
    else
    {
      // Form falidation successful
      // Start upload
      form.classList.add('was-validated');
      catidFieldValidity();
      window.scrollTo(0, 0);

      return true;
    }
  }

  uppy.on('complete', (result) => {
    // Re-initialize the form
    document.getElementById('adminForm').classList.remove('was-validated');
    document.getElementById('system-message-container').innerHTML = '';
    catidFieldValidity(true);
  });

}; //end callback

if(document.readyState === "complete" || (document.readyState !== "loading" && !document.documentElement.doScroll))
{
  callback();
} else {
  document.addEventListener("DOMContentLoaded", callback);
}

})();

/******/ })()
;
//# sourceMappingURL=uppy-uploader.js.map