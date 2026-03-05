/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/scss/lazy-load/lazy-load.scss":
/*!*******************************************!*\
  !*** ./src/scss/lazy-load/lazy-load.scss ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


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
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
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
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!***************************************!*\
  !*** ./src/js/lazy-load/lazy-load.js ***!
  \***************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _scss_lazy_load_lazy_load_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../scss/lazy-load/lazy-load.scss */ "./src/scss/lazy-load/lazy-load.scss");
/**
 * @file
 * Lazy load images using IntersectionObserver for data-src attribute.
 */

(function (Drupal, once) {
  "use strict";

  Drupal.behaviors.more_fields_lazyLoadImages = {
    attach: function (context, settings) {
      // Use once() to avoid multiple initializations.
      const images = once("lazy-load", "img[data-src]", context);
      if (!images.length) {
        return;
      }
      if (!window.IntersectionObserver) {
        // Fallback for older browsers: load all images immediately.
        images.forEach(function (img) {
          img.src = img.dataset.src;
        });
        return;
      }

      // Create one observer with dynamic rootMargin per image.
      // We'll recreate options for each image, but IntersectionObserver
      // does not support per-image rootMargin. So we'll use a single observer
      // with the maximum threshold? Better: create observer per image?
      // For simplicity, we'll use a single observer with a fixed margin of 200px,
      // and rely on the data-threshold attribute to adjust if needed.
      // However, we can't change rootMargin dynamically.
      // Alternative: use a single observer with a high default margin.
      // But to respect per-image threshold, we could set rootMargin to '200px'
      // and rely on the fact that it's just a margin, not a critical setting.
      // Another approach: create an observer per image, but that's heavy.
      // We'll use a single observer with margin from the first image or default.

      // Simple approach: use a fixed rootMargin of 200px (or from settings).
      // For more accuracy, you could implement a custom solution, but this is fine.
      const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            img.removeAttribute("data-src");
            img.removeAttribute("data-threshold");
            observer.unobserve(img);
          }
        });
      }, {
        rootMargin: "200px 0px"
      });
      images.forEach(function (img) {
        // Optionally read threshold from data-threshold to adjust?
        // For now we ignore per-image threshold.
        observer.observe(img);
      });
    }
  };
})(Drupal, once);
})();

/******/ })()
;
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiLi4vanMvbGF6eS1sb2FkLmpzIiwibWFwcGluZ3MiOiI7Ozs7Ozs7Ozs7O0FBQUE7Ozs7Ozs7VUNBQTtVQUNBOztVQUVBO1VBQ0E7VUFDQTtVQUNBO1VBQ0E7VUFDQTtVQUNBO1VBQ0E7VUFDQTtVQUNBO1VBQ0E7VUFDQTtVQUNBOztVQUVBO1VBQ0E7O1VBRUE7VUFDQTtVQUNBOzs7OztXQ3RCQTtXQUNBO1dBQ0E7V0FDQSx1REFBdUQsaUJBQWlCO1dBQ3hFO1dBQ0EsZ0RBQWdELGFBQWE7V0FDN0Q7Ozs7Ozs7Ozs7OztBQ05BO0FBQ0E7QUFDQTtBQUNBO0FBQzZDO0FBQzdDLENBQUMsVUFBVUEsTUFBTSxFQUFFQyxJQUFJLEVBQUU7RUFDdkIsWUFBWTs7RUFFWkQsTUFBTSxDQUFDRSxTQUFTLENBQUNDLDBCQUEwQixHQUFHO0lBQzVDQyxNQUFNLEVBQUUsU0FBQUEsQ0FBVUMsT0FBTyxFQUFFQyxRQUFRLEVBQUU7TUFDbkM7TUFDQSxNQUFNQyxNQUFNLEdBQUdOLElBQUksQ0FBQyxXQUFXLEVBQUUsZUFBZSxFQUFFSSxPQUFPLENBQUM7TUFFMUQsSUFBSSxDQUFDRSxNQUFNLENBQUNDLE1BQU0sRUFBRTtRQUNsQjtNQUNGO01BRUEsSUFBSSxDQUFDQyxNQUFNLENBQUNDLG9CQUFvQixFQUFFO1FBQ2hDO1FBQ0FILE1BQU0sQ0FBQ0ksT0FBTyxDQUFDLFVBQVVDLEdBQUcsRUFBRTtVQUM1QkEsR0FBRyxDQUFDQyxHQUFHLEdBQUdELEdBQUcsQ0FBQ0UsT0FBTyxDQUFDRCxHQUFHO1FBQzNCLENBQUMsQ0FBQztRQUNGO01BQ0Y7O01BRUE7TUFDQTtNQUNBO01BQ0E7TUFDQTtNQUNBO01BQ0E7TUFDQTtNQUNBO01BQ0E7TUFDQTtNQUNBOztNQUVBO01BQ0E7TUFDQSxNQUFNRSxRQUFRLEdBQUcsSUFBSUwsb0JBQW9CLENBQ3ZDLFVBQVVNLE9BQU8sRUFBRTtRQUNqQkEsT0FBTyxDQUFDTCxPQUFPLENBQUMsVUFBVU0sS0FBSyxFQUFFO1VBQy9CLElBQUlBLEtBQUssQ0FBQ0MsY0FBYyxFQUFFO1lBQ3hCLE1BQU1OLEdBQUcsR0FBR0ssS0FBSyxDQUFDRSxNQUFNO1lBQ3hCUCxHQUFHLENBQUNDLEdBQUcsR0FBR0QsR0FBRyxDQUFDRSxPQUFPLENBQUNELEdBQUc7WUFDekJELEdBQUcsQ0FBQ1EsZUFBZSxDQUFDLFVBQVUsQ0FBQztZQUMvQlIsR0FBRyxDQUFDUSxlQUFlLENBQUMsZ0JBQWdCLENBQUM7WUFDckNMLFFBQVEsQ0FBQ00sU0FBUyxDQUFDVCxHQUFHLENBQUM7VUFDekI7UUFDRixDQUFDLENBQUM7TUFDSixDQUFDLEVBQ0Q7UUFDRVUsVUFBVSxFQUFFO01BQ2QsQ0FDRixDQUFDO01BRURmLE1BQU0sQ0FBQ0ksT0FBTyxDQUFDLFVBQVVDLEdBQUcsRUFBRTtRQUM1QjtRQUNBO1FBQ0FHLFFBQVEsQ0FBQ1EsT0FBTyxDQUFDWCxHQUFHLENBQUM7TUFDdkIsQ0FBQyxDQUFDO0lBQ0o7RUFDRixDQUFDO0FBQ0gsQ0FBQyxFQUFFWixNQUFNLEVBQUVDLElBQUksQ0FBQyxDIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vQHN0ZXBoYW5lODg4L3didS1hdG9taXF1ZS10aGVtZS8uL3NyYy9zY3NzL2xhenktbG9hZC9sYXp5LWxvYWQuc2NzcyIsIndlYnBhY2s6Ly9Ac3RlcGhhbmU4ODgvd2J1LWF0b21pcXVlLXRoZW1lL3dlYnBhY2svYm9vdHN0cmFwIiwid2VicGFjazovL0BzdGVwaGFuZTg4OC93YnUtYXRvbWlxdWUtdGhlbWUvd2VicGFjay9ydW50aW1lL21ha2UgbmFtZXNwYWNlIG9iamVjdCIsIndlYnBhY2s6Ly9Ac3RlcGhhbmU4ODgvd2J1LWF0b21pcXVlLXRoZW1lLy4vc3JjL2pzL2xhenktbG9hZC9sYXp5LWxvYWQuanMiXSwic291cmNlc0NvbnRlbnQiOlsiLy8gZXh0cmFjdGVkIGJ5IG1pbmktY3NzLWV4dHJhY3QtcGx1Z2luXG5leHBvcnQge307IiwiLy8gVGhlIG1vZHVsZSBjYWNoZVxudmFyIF9fd2VicGFja19tb2R1bGVfY2FjaGVfXyA9IHt9O1xuXG4vLyBUaGUgcmVxdWlyZSBmdW5jdGlvblxuZnVuY3Rpb24gX193ZWJwYWNrX3JlcXVpcmVfXyhtb2R1bGVJZCkge1xuXHQvLyBDaGVjayBpZiBtb2R1bGUgaXMgaW4gY2FjaGVcblx0dmFyIGNhY2hlZE1vZHVsZSA9IF9fd2VicGFja19tb2R1bGVfY2FjaGVfX1ttb2R1bGVJZF07XG5cdGlmIChjYWNoZWRNb2R1bGUgIT09IHVuZGVmaW5lZCkge1xuXHRcdHJldHVybiBjYWNoZWRNb2R1bGUuZXhwb3J0cztcblx0fVxuXHQvLyBDcmVhdGUgYSBuZXcgbW9kdWxlIChhbmQgcHV0IGl0IGludG8gdGhlIGNhY2hlKVxuXHR2YXIgbW9kdWxlID0gX193ZWJwYWNrX21vZHVsZV9jYWNoZV9fW21vZHVsZUlkXSA9IHtcblx0XHQvLyBubyBtb2R1bGUuaWQgbmVlZGVkXG5cdFx0Ly8gbm8gbW9kdWxlLmxvYWRlZCBuZWVkZWRcblx0XHRleHBvcnRzOiB7fVxuXHR9O1xuXG5cdC8vIEV4ZWN1dGUgdGhlIG1vZHVsZSBmdW5jdGlvblxuXHRfX3dlYnBhY2tfbW9kdWxlc19fW21vZHVsZUlkXShtb2R1bGUsIG1vZHVsZS5leHBvcnRzLCBfX3dlYnBhY2tfcmVxdWlyZV9fKTtcblxuXHQvLyBSZXR1cm4gdGhlIGV4cG9ydHMgb2YgdGhlIG1vZHVsZVxuXHRyZXR1cm4gbW9kdWxlLmV4cG9ydHM7XG59XG5cbiIsIi8vIGRlZmluZSBfX2VzTW9kdWxlIG9uIGV4cG9ydHNcbl9fd2VicGFja19yZXF1aXJlX18uciA9IChleHBvcnRzKSA9PiB7XG5cdGlmKHR5cGVvZiBTeW1ib2wgIT09ICd1bmRlZmluZWQnICYmIFN5bWJvbC50b1N0cmluZ1RhZykge1xuXHRcdE9iamVjdC5kZWZpbmVQcm9wZXJ0eShleHBvcnRzLCBTeW1ib2wudG9TdHJpbmdUYWcsIHsgdmFsdWU6ICdNb2R1bGUnIH0pO1xuXHR9XG5cdE9iamVjdC5kZWZpbmVQcm9wZXJ0eShleHBvcnRzLCAnX19lc01vZHVsZScsIHsgdmFsdWU6IHRydWUgfSk7XG59OyIsIi8qKlxuICogQGZpbGVcbiAqIExhenkgbG9hZCBpbWFnZXMgdXNpbmcgSW50ZXJzZWN0aW9uT2JzZXJ2ZXIgZm9yIGRhdGEtc3JjIGF0dHJpYnV0ZS5cbiAqL1xuaW1wb3J0IFwiLi4vLi4vc2Nzcy9sYXp5LWxvYWQvbGF6eS1sb2FkLnNjc3NcIjtcbihmdW5jdGlvbiAoRHJ1cGFsLCBvbmNlKSB7XG4gIFwidXNlIHN0cmljdFwiO1xuXG4gIERydXBhbC5iZWhhdmlvcnMubW9yZV9maWVsZHNfbGF6eUxvYWRJbWFnZXMgPSB7XG4gICAgYXR0YWNoOiBmdW5jdGlvbiAoY29udGV4dCwgc2V0dGluZ3MpIHtcbiAgICAgIC8vIFVzZSBvbmNlKCkgdG8gYXZvaWQgbXVsdGlwbGUgaW5pdGlhbGl6YXRpb25zLlxuICAgICAgY29uc3QgaW1hZ2VzID0gb25jZShcImxhenktbG9hZFwiLCBcImltZ1tkYXRhLXNyY11cIiwgY29udGV4dCk7XG5cbiAgICAgIGlmICghaW1hZ2VzLmxlbmd0aCkge1xuICAgICAgICByZXR1cm47XG4gICAgICB9XG5cbiAgICAgIGlmICghd2luZG93LkludGVyc2VjdGlvbk9ic2VydmVyKSB7XG4gICAgICAgIC8vIEZhbGxiYWNrIGZvciBvbGRlciBicm93c2VyczogbG9hZCBhbGwgaW1hZ2VzIGltbWVkaWF0ZWx5LlxuICAgICAgICBpbWFnZXMuZm9yRWFjaChmdW5jdGlvbiAoaW1nKSB7XG4gICAgICAgICAgaW1nLnNyYyA9IGltZy5kYXRhc2V0LnNyYztcbiAgICAgICAgfSk7XG4gICAgICAgIHJldHVybjtcbiAgICAgIH1cblxuICAgICAgLy8gQ3JlYXRlIG9uZSBvYnNlcnZlciB3aXRoIGR5bmFtaWMgcm9vdE1hcmdpbiBwZXIgaW1hZ2UuXG4gICAgICAvLyBXZSdsbCByZWNyZWF0ZSBvcHRpb25zIGZvciBlYWNoIGltYWdlLCBidXQgSW50ZXJzZWN0aW9uT2JzZXJ2ZXJcbiAgICAgIC8vIGRvZXMgbm90IHN1cHBvcnQgcGVyLWltYWdlIHJvb3RNYXJnaW4uIFNvIHdlJ2xsIHVzZSBhIHNpbmdsZSBvYnNlcnZlclxuICAgICAgLy8gd2l0aCB0aGUgbWF4aW11bSB0aHJlc2hvbGQ/IEJldHRlcjogY3JlYXRlIG9ic2VydmVyIHBlciBpbWFnZT9cbiAgICAgIC8vIEZvciBzaW1wbGljaXR5LCB3ZSdsbCB1c2UgYSBzaW5nbGUgb2JzZXJ2ZXIgd2l0aCBhIGZpeGVkIG1hcmdpbiBvZiAyMDBweCxcbiAgICAgIC8vIGFuZCByZWx5IG9uIHRoZSBkYXRhLXRocmVzaG9sZCBhdHRyaWJ1dGUgdG8gYWRqdXN0IGlmIG5lZWRlZC5cbiAgICAgIC8vIEhvd2V2ZXIsIHdlIGNhbid0IGNoYW5nZSByb290TWFyZ2luIGR5bmFtaWNhbGx5LlxuICAgICAgLy8gQWx0ZXJuYXRpdmU6IHVzZSBhIHNpbmdsZSBvYnNlcnZlciB3aXRoIGEgaGlnaCBkZWZhdWx0IG1hcmdpbi5cbiAgICAgIC8vIEJ1dCB0byByZXNwZWN0IHBlci1pbWFnZSB0aHJlc2hvbGQsIHdlIGNvdWxkIHNldCByb290TWFyZ2luIHRvICcyMDBweCdcbiAgICAgIC8vIGFuZCByZWx5IG9uIHRoZSBmYWN0IHRoYXQgaXQncyBqdXN0IGEgbWFyZ2luLCBub3QgYSBjcml0aWNhbCBzZXR0aW5nLlxuICAgICAgLy8gQW5vdGhlciBhcHByb2FjaDogY3JlYXRlIGFuIG9ic2VydmVyIHBlciBpbWFnZSwgYnV0IHRoYXQncyBoZWF2eS5cbiAgICAgIC8vIFdlJ2xsIHVzZSBhIHNpbmdsZSBvYnNlcnZlciB3aXRoIG1hcmdpbiBmcm9tIHRoZSBmaXJzdCBpbWFnZSBvciBkZWZhdWx0LlxuXG4gICAgICAvLyBTaW1wbGUgYXBwcm9hY2g6IHVzZSBhIGZpeGVkIHJvb3RNYXJnaW4gb2YgMjAwcHggKG9yIGZyb20gc2V0dGluZ3MpLlxuICAgICAgLy8gRm9yIG1vcmUgYWNjdXJhY3ksIHlvdSBjb3VsZCBpbXBsZW1lbnQgYSBjdXN0b20gc29sdXRpb24sIGJ1dCB0aGlzIGlzIGZpbmUuXG4gICAgICBjb25zdCBvYnNlcnZlciA9IG5ldyBJbnRlcnNlY3Rpb25PYnNlcnZlcihcbiAgICAgICAgZnVuY3Rpb24gKGVudHJpZXMpIHtcbiAgICAgICAgICBlbnRyaWVzLmZvckVhY2goZnVuY3Rpb24gKGVudHJ5KSB7XG4gICAgICAgICAgICBpZiAoZW50cnkuaXNJbnRlcnNlY3RpbmcpIHtcbiAgICAgICAgICAgICAgY29uc3QgaW1nID0gZW50cnkudGFyZ2V0O1xuICAgICAgICAgICAgICBpbWcuc3JjID0gaW1nLmRhdGFzZXQuc3JjO1xuICAgICAgICAgICAgICBpbWcucmVtb3ZlQXR0cmlidXRlKFwiZGF0YS1zcmNcIik7XG4gICAgICAgICAgICAgIGltZy5yZW1vdmVBdHRyaWJ1dGUoXCJkYXRhLXRocmVzaG9sZFwiKTtcbiAgICAgICAgICAgICAgb2JzZXJ2ZXIudW5vYnNlcnZlKGltZyk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgfSk7XG4gICAgICAgIH0sXG4gICAgICAgIHtcbiAgICAgICAgICByb290TWFyZ2luOiBcIjIwMHB4IDBweFwiLFxuICAgICAgICB9LFxuICAgICAgKTtcblxuICAgICAgaW1hZ2VzLmZvckVhY2goZnVuY3Rpb24gKGltZykge1xuICAgICAgICAvLyBPcHRpb25hbGx5IHJlYWQgdGhyZXNob2xkIGZyb20gZGF0YS10aHJlc2hvbGQgdG8gYWRqdXN0P1xuICAgICAgICAvLyBGb3Igbm93IHdlIGlnbm9yZSBwZXItaW1hZ2UgdGhyZXNob2xkLlxuICAgICAgICBvYnNlcnZlci5vYnNlcnZlKGltZyk7XG4gICAgICB9KTtcbiAgICB9LFxuICB9O1xufSkoRHJ1cGFsLCBvbmNlKTtcbiJdLCJuYW1lcyI6WyJEcnVwYWwiLCJvbmNlIiwiYmVoYXZpb3JzIiwibW9yZV9maWVsZHNfbGF6eUxvYWRJbWFnZXMiLCJhdHRhY2giLCJjb250ZXh0Iiwic2V0dGluZ3MiLCJpbWFnZXMiLCJsZW5ndGgiLCJ3aW5kb3ciLCJJbnRlcnNlY3Rpb25PYnNlcnZlciIsImZvckVhY2giLCJpbWciLCJzcmMiLCJkYXRhc2V0Iiwib2JzZXJ2ZXIiLCJlbnRyaWVzIiwiZW50cnkiLCJpc0ludGVyc2VjdGluZyIsInRhcmdldCIsInJlbW92ZUF0dHJpYnV0ZSIsInVub2JzZXJ2ZSIsInJvb3RNYXJnaW4iLCJvYnNlcnZlIl0sInNvdXJjZVJvb3QiOiIifQ==