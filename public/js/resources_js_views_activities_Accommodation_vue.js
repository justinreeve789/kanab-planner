"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["resources_js_views_activities_Accommodation_vue"],{

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/views/activities/Accommodation.vue?vue&type=script&setup=true&lang=js":
/*!************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/views/activities/Accommodation.vue?vue&type=script&setup=true&lang=js ***!
  \************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");
/* harmony import */ var _components_ActivityButtons_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../components/ActivityButtons.vue */ "./resources/js/views/components/ActivityButtons.vue");
/* harmony import */ var _modals_MapModal_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../modals/MapModal.vue */ "./resources/js/views/modals/MapModal.vue");
/* harmony import */ var _panels_Side_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../panels/Side.vue */ "./resources/js/views/panels/Side.vue");
/* harmony import */ var _stats_TrailStats_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../stats/TrailStats.vue */ "./resources/js/views/stats/TrailStats.vue");
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }





var __default__ = {
  components: {
    ActivityButtons: _components_ActivityButtons_vue__WEBPACK_IMPORTED_MODULE_1__["default"],
    MapModal: _modals_MapModal_vue__WEBPACK_IMPORTED_MODULE_2__["default"]
  },
  data: function data() {
    return {
      expanded: false
    };
  },
  methods: {
    formatTime: function formatTime(time24) {
      if (!time24) return '';
      var _time24$split = time24.split(':'),
        _time24$split2 = _slicedToArray(_time24$split, 2),
        hours = _time24$split2[0],
        minutes = _time24$split2[1];
      var date = new Date();
      date.setHours(hours);
      date.setMinutes(minutes);
      var formatted = date.toLocaleTimeString([], {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
      });
      return formatted.replace(/AM|PM/, function (match) {
        return match.toLowerCase();
      });
    }
  },
  props: {
    activity: Object,
    resource: Object
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*@__PURE__*/Object.assign(__default__, {
  __name: 'Accommodation',
  setup: function setup(__props, _ref) {
    var __expose = _ref.expose;
    __expose();
    var __returned__ = {
      ref: vue__WEBPACK_IMPORTED_MODULE_0__.ref,
      onMounted: vue__WEBPACK_IMPORTED_MODULE_0__.onMounted,
      onUnmounted: vue__WEBPACK_IMPORTED_MODULE_0__.onUnmounted,
      watch: vue__WEBPACK_IMPORTED_MODULE_0__.watch,
      ActivityButtons: _components_ActivityButtons_vue__WEBPACK_IMPORTED_MODULE_1__["default"],
      MapModal: _modals_MapModal_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
      Side: _panels_Side_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
      TrailStats: _stats_TrailStats_vue__WEBPACK_IMPORTED_MODULE_4__["default"]
    };
    Object.defineProperty(__returned__, '__isScriptSetup', {
      enumerable: false,
      value: true
    });
    return __returned__;
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/views/components/Map.vue?vue&type=script&lang=js":
/*!***************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/views/components/Map.vue?vue&type=script&lang=js ***!
  \***************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! axios */ "./node_modules/axios/lib/axios.js");
/* harmony import */ var leaflet_dist_leaflet_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! leaflet/dist/leaflet.css */ "./node_modules/leaflet/dist/leaflet.css");
/* harmony import */ var _vue_leaflet_vue_leaflet__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @vue-leaflet/vue-leaflet */ "./node_modules/@vue-leaflet/vue-leaflet/dist/vue-leaflet.es.js");



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  components: {
    LCircleMarker: _vue_leaflet_vue_leaflet__WEBPACK_IMPORTED_MODULE_1__.LCircleMarker,
    LGeoJson: _vue_leaflet_vue_leaflet__WEBPACK_IMPORTED_MODULE_1__.LGeoJson,
    LIcon: _vue_leaflet_vue_leaflet__WEBPACK_IMPORTED_MODULE_1__.LIcon,
    LMap: _vue_leaflet_vue_leaflet__WEBPACK_IMPORTED_MODULE_1__.LMap,
    LMarker: _vue_leaflet_vue_leaflet__WEBPACK_IMPORTED_MODULE_1__.LMarker,
    LTileLayer: _vue_leaflet_vue_leaflet__WEBPACK_IMPORTED_MODULE_1__.LTileLayer,
    LTooltip: _vue_leaflet_vue_leaflet__WEBPACK_IMPORTED_MODULE_1__.LTooltip
  },
  data: function data() {
    return {
      geoOptions: {
        className: 'trail-path',
        color: '#ff0080',
        // Replace with your gradient colors
        hourOptions: {
          permanent: true
        },
        weight: 5,
        // Adjust the line thickness
        opacity: 0.8,
        // Adjust the opacity
        shadowBlur: 15,
        // Add a blur effect for the glow
        shadowColor: '#bb0080' // Set the glow color to match your gradient
      },
      geoJson: {},
      geoJsonMarkers: {},
      zoom: 15
    };
  },
  methods: {
    getGeoJson: function getGeoJson() {
      var _this = this;
      axios__WEBPACK_IMPORTED_MODULE_2__["default"].get('/api/v1/trail/' + this.resource.id + '/geojson').then(function (response) {
        _this.geoJson = response.data;
      })["catch"](function (error) {
        console.error('Error fetching geoJson data:', error);
      });
    },
    getGeoJsonMarkers: function getGeoJsonMarkers() {
      var _this2 = this;
      axios__WEBPACK_IMPORTED_MODULE_2__["default"].get('/api/v1/trail/' + this.resource.id + '/geojson/analyze').then(function (response) {
        if (response.data.time_hours) {
          _this2.geoJsonMarkers = response.data.time_hours;
          var map = L.map('map');
          // for (let h = 0; h < this.geoJsonMarkers.length; h++) {
          //     let marker = new L.marker(this.geoJsonMarkers[h]);
          //     marker.bindTooltip(h + 1 + 'h', { permanent: true, className: "hour-label", offset: [0, 0] });
          //     marker.addTo(map);
          // }
        }
      })["catch"](function (error) {
        console.error('Error fetching geoJson markers:', error);
      });
    }
  },
  mounted: function mounted() {
    this.getGeoJson();
    // this.getGeoJsonMarkers();
  },
  props: {
    resource: Object
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/views/modals/MapModal.vue?vue&type=script&lang=js":
/*!****************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/views/modals/MapModal.vue?vue&type=script&lang=js ***!
  \****************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _components_Map_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../components/Map.vue */ "./resources/js/views/components/Map.vue");

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  components: {
    Map: _components_Map_vue__WEBPACK_IMPORTED_MODULE_0__["default"]
  },
  data: function data() {
    return {
      expanded: false
    };
  },
  props: {
    resource: Object,
    unique: String,
    zoom: Number
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/views/activities/Accommodation.vue?vue&type=template&id=334cc78f":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/views/activities/Accommodation.vue?vue&type=template&id=334cc78f ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

var _hoisted_1 = ["data-id"];
var _hoisted_2 = {
  "class": "collapse-title flex items-center p-2 justify-between w-full bg-gradient-to-r from-blue-100 to-cyan-100"
};
var _hoisted_3 = {
  "class": "title w-1/2 flex items-center space-x-2 text-left"
};
var _hoisted_4 = {
  "class": "text-xl"
};
var _hoisted_5 = {
  key: 0
};
var _hoisted_6 = {
  key: 1
};
var _hoisted_7 = {
  "class": "flex space-x-4 items-center mr-8 h-full"
};
var _hoisted_8 = {
  "class": "badge badge-lg bg-blue-400 border-blue-400 mr-2 text-white hidden md:flex"
};
var _hoisted_9 = {
  key: 0
};
var _hoisted_10 = {
  key: 1
};
var _hoisted_11 = {
  key: 0,
  "class": "collapse-content min-h-[150px] items-stretch overflow-hidden w-full bg-gray-200 p-2 bg-gradient-to-r from-white to-cyan-100"
};
var _hoisted_12 = {
  "class": "lg:flex lg:justify-between"
};
var _hoisted_13 = {
  key: 0,
  "class": "w-full lg:w-2/3 lg:flex lg:flex-col"
};
var _hoisted_14 = {
  key: 1,
  "class": "w-full lg:w-2/3 lg:flex lg:flex-col"
};
var _hoisted_15 = {
  "class": "order-2 lg:order-1"
};
var _hoisted_16 = {
  "class": "sm:flex sm:flex-row justify-between lg:flex-col order-1 lg:order-2"
};
var _hoisted_17 = {
  key: 2,
  "class": "w-[calc(100%+16px)] -ml-2 -mb-4 lg:w-1/3 mt-4 lg:pl-4 lg:-mt-2 lg:-mr-2"
};
function render(_ctx, _cache, $props, $setup, $data, $options) {
  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
    "data-id": $props.resource.id,
    "class": "resource-details collapse collapse-arrow",
    "data-type": "trail"
  }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
    "onUpdate:modelValue": _cache[0] || (_cache[0] = function ($event) {
      return $data.expanded = $event;
    }),
    type: "checkbox"
  }, null, 512 /* NEED_PATCH */), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelCheckbox, $data.expanded]]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_2, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [_cache[1] || (_cache[1] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
    "class": "icon pr-1 shrink-0"
  }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("img", {
    src: "/images/icons/thumbs-up.svg",
    alt: "Accommodation"
  })], -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_4, [$props.activity.activity_type.name == 'checking-in' ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_5, "Arrive at")) : $props.activity.activity_type.name == 'checking-out' ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_6, "Depart from")) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.resource.name), 1 /* TEXT */)])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_7, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_8, [$props.activity.activity_type.name == 'checking-in' ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_9, " Check-in: " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formatTime($props.resource.check_in)), 1 /* TEXT */)) : $props.activity.activity_type.name == 'checking-out' ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_10, " Check-out: " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formatTime($props.resource.check_out)), 1 /* TEXT */)) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)])])]), $data.expanded ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_11, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_12, [$props.activity.activity_type.name == 'checking-out' ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_13, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.activity.phrase) + " ", 1 /* TEXT */), _cache[2] || (_cache[2] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, "Leave a review of your accommodations!", -1 /* HOISTED */))])) : $props.activity.activity_type.name == 'checking-in' ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_14, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_15, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.resource.description), 1 /* TEXT */)]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_16, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["ActivityButtons"], {
    resource: $props.resource,
    resource_table: 'accommodations',
    "class": "w-full my-4",
    type: "checking-in"
  }, null, 8 /* PROPS */, ["resource"])])])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true), $props.activity.activity_type.name == 'checking-in' && $props.resource.images ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_17, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Side"], {
    images: $props.resource.images,
    resource: $props.resource,
    type: 'accommodation'
  }, null, 8 /* PROPS */, ["images", "resource"])])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)])])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)], 8 /* PROPS */, _hoisted_1);
}

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/views/components/Map.vue?vue&type=template&id=d7a17ffc":
/*!*******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/views/components/Map.vue?vue&type=template&id=d7a17ffc ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

function render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_l_tile_layer = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("l-tile-layer");
  var _component_l_geo_json = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("l-geo-json");
  var _component_l_map = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("l-map");
  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_l_map, {
    ref: "map",
    zoom: $data.zoom,
    "onUpdate:zoom": _cache[0] || (_cache[0] = function ($event) {
      return $data.zoom = $event;
    }),
    center: [$props.resource.latitude, $props.resource.longitude]
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_l_tile_layer, {
        url: "https://caltopo.com/tile/mb_hybrid/{z}/{x}/{y}.png",
        "layer-type": "base",
        name: "Map"
      }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_l_geo_json, {
        geojson: $data.geoJson,
        "options-style": $data.geoOptions
      }, null, 8 /* PROPS */, ["geojson", "options-style"])];
    }),
    _: 1 /* STABLE */
  }, 8 /* PROPS */, ["zoom", "center"]);
}

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/views/modals/MapModal.vue?vue&type=template&id=74e1bcaa":
/*!********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/views/modals/MapModal.vue?vue&type=template&id=74e1bcaa ***!
  \********************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

var _hoisted_1 = ["id"];
var _hoisted_2 = ["id"];
var _hoisted_3 = {
  "class": "modal-box p-2 max-w-none w-full",
  style: {
    "height": "100%"
  }
};
var _hoisted_4 = ["for"];
function render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_Map = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Map");
  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
    type: "checkbox",
    id: 'resource-' + $props.unique + '-map',
    "class": "modal-toggle",
    "onUpdate:modelValue": _cache[0] || (_cache[0] = function ($event) {
      return $data.expanded = $event;
    })
  }, null, 8 /* PROPS */, _hoisted_1), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelCheckbox, $data.expanded]]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
    "class": "modal",
    id: 'resource-' + $props.unique + '-map-modal'
  }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Map, {
    resource: $props.resource,
    zoom: $props.zoom
  }, null, 8 /* PROPS */, ["resource", "zoom"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", {
    "for": 'resource-' + $props.unique + '-map'
  }, _cache[1] || (_cache[1] = [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", {
    "class": "close-modal btn btn-md btn-ghost bg-transparent border-green-800 text-2xl text-green-800 absolute right-2 top-2"
  }, "✕", -1 /* HOISTED */)]), 8 /* PROPS */, _hoisted_4)])], 8 /* PROPS */, _hoisted_2), _cache[2] || (_cache[2] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", {
    "for": 'activity-' + 'hash' + '-map',
    "class": "modal-backdrop"
  }, null, -1 /* HOISTED */))], 64 /* STABLE_FRAGMENT */);
}

/***/ }),

/***/ "./resources/js/views/activities/Accommodation.vue":
/*!*********************************************************!*\
  !*** ./resources/js/views/activities/Accommodation.vue ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Accommodation_vue_vue_type_template_id_334cc78f__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Accommodation.vue?vue&type=template&id=334cc78f */ "./resources/js/views/activities/Accommodation.vue?vue&type=template&id=334cc78f");
/* harmony import */ var _Accommodation_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Accommodation.vue?vue&type=script&setup=true&lang=js */ "./resources/js/views/activities/Accommodation.vue?vue&type=script&setup=true&lang=js");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;
const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__["default"])(_Accommodation_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_Accommodation_vue_vue_type_template_id_334cc78f__WEBPACK_IMPORTED_MODULE_0__.render],['__file',"resources/js/views/activities/Accommodation.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/views/activities/Accommodation.vue?vue&type=script&setup=true&lang=js":
/*!********************************************************************************************!*\
  !*** ./resources/js/views/activities/Accommodation.vue?vue&type=script&setup=true&lang=js ***!
  \********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Accommodation_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Accommodation_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Accommodation.vue?vue&type=script&setup=true&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/views/activities/Accommodation.vue?vue&type=script&setup=true&lang=js");
 

/***/ }),

/***/ "./resources/js/views/activities/Accommodation.vue?vue&type=template&id=334cc78f":
/*!***************************************************************************************!*\
  !*** ./resources/js/views/activities/Accommodation.vue?vue&type=template&id=334cc78f ***!
  \***************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Accommodation_vue_vue_type_template_id_334cc78f__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Accommodation_vue_vue_type_template_id_334cc78f__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Accommodation.vue?vue&type=template&id=334cc78f */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/views/activities/Accommodation.vue?vue&type=template&id=334cc78f");


/***/ }),

/***/ "./resources/js/views/components/Map.vue":
/*!***********************************************!*\
  !*** ./resources/js/views/components/Map.vue ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Map_vue_vue_type_template_id_d7a17ffc__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Map.vue?vue&type=template&id=d7a17ffc */ "./resources/js/views/components/Map.vue?vue&type=template&id=d7a17ffc");
/* harmony import */ var _Map_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Map.vue?vue&type=script&lang=js */ "./resources/js/views/components/Map.vue?vue&type=script&lang=js");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;
const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__["default"])(_Map_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_Map_vue_vue_type_template_id_d7a17ffc__WEBPACK_IMPORTED_MODULE_0__.render],['__file',"resources/js/views/components/Map.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/views/components/Map.vue?vue&type=script&lang=js":
/*!***********************************************************************!*\
  !*** ./resources/js/views/components/Map.vue?vue&type=script&lang=js ***!
  \***********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Map_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Map_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Map.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/views/components/Map.vue?vue&type=script&lang=js");
 

/***/ }),

/***/ "./resources/js/views/components/Map.vue?vue&type=template&id=d7a17ffc":
/*!*****************************************************************************!*\
  !*** ./resources/js/views/components/Map.vue?vue&type=template&id=d7a17ffc ***!
  \*****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Map_vue_vue_type_template_id_d7a17ffc__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Map_vue_vue_type_template_id_d7a17ffc__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Map.vue?vue&type=template&id=d7a17ffc */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/views/components/Map.vue?vue&type=template&id=d7a17ffc");


/***/ }),

/***/ "./resources/js/views/modals/MapModal.vue":
/*!************************************************!*\
  !*** ./resources/js/views/modals/MapModal.vue ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _MapModal_vue_vue_type_template_id_74e1bcaa__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./MapModal.vue?vue&type=template&id=74e1bcaa */ "./resources/js/views/modals/MapModal.vue?vue&type=template&id=74e1bcaa");
/* harmony import */ var _MapModal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./MapModal.vue?vue&type=script&lang=js */ "./resources/js/views/modals/MapModal.vue?vue&type=script&lang=js");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;
const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__["default"])(_MapModal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_MapModal_vue_vue_type_template_id_74e1bcaa__WEBPACK_IMPORTED_MODULE_0__.render],['__file',"resources/js/views/modals/MapModal.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/views/modals/MapModal.vue?vue&type=script&lang=js":
/*!************************************************************************!*\
  !*** ./resources/js/views/modals/MapModal.vue?vue&type=script&lang=js ***!
  \************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_MapModal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_MapModal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./MapModal.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/views/modals/MapModal.vue?vue&type=script&lang=js");
 

/***/ }),

/***/ "./resources/js/views/modals/MapModal.vue?vue&type=template&id=74e1bcaa":
/*!******************************************************************************!*\
  !*** ./resources/js/views/modals/MapModal.vue?vue&type=template&id=74e1bcaa ***!
  \******************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_MapModal_vue_vue_type_template_id_74e1bcaa__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_MapModal_vue_vue_type_template_id_74e1bcaa__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./MapModal.vue?vue&type=template&id=74e1bcaa */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/views/modals/MapModal.vue?vue&type=template&id=74e1bcaa");


/***/ })

}]);