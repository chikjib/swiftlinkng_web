"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["resource/js/components/users/edituser"],{

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/users/edituser.vue?vue&type=script&lang=js&":
/*!*********************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/users/edituser.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
var token = JSON.parse(sessionStorage.getItem("token"));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  data: function data() {
    return {
      user: {}
    };
  },
  created: function created() {
    var _this = this;

    axios.get("/api/users/".concat(this.$route.params.id), {
      headers: {
        Authorization: "Bearer ".concat(token),
        "Content-Type": "application/json"
      }
    }).then(function (response) {
      console.log(response.data);
      _this.user = response.data.data;
    });
  },
  methods: {
    updatePost: function updatePost() {
      var _this2 = this;

      axios.put("/api/users/".concat(this.$route.params.id), this.user, {
        headers: {
          Authorization: "Bearer ".concat(token),
          "Content-Type": "application/json"
        }
      }).then(function (response) {
        console.log(response.data);

        _this2.$router.push({
          name: "users"
        });
      });
    }
  }
});

/***/ }),

/***/ "./resources/js/components/users/edituser.vue":
/*!****************************************************!*\
  !*** ./resources/js/components/users/edituser.vue ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _edituser_vue_vue_type_template_id_db159bf2___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./edituser.vue?vue&type=template&id=db159bf2& */ "./resources/js/components/users/edituser.vue?vue&type=template&id=db159bf2&");
/* harmony import */ var _edituser_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./edituser.vue?vue&type=script&lang=js& */ "./resources/js/components/users/edituser.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _edituser_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _edituser_vue_vue_type_template_id_db159bf2___WEBPACK_IMPORTED_MODULE_0__.render,
  _edituser_vue_vue_type_template_id_db159bf2___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "resources/js/components/users/edituser.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./resources/js/components/users/edituser.vue?vue&type=script&lang=js&":
/*!*****************************************************************************!*\
  !*** ./resources/js/components/users/edituser.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_edituser_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./edituser.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/users/edituser.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_edituser_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./resources/js/components/users/edituser.vue?vue&type=template&id=db159bf2&":
/*!***********************************************************************************!*\
  !*** ./resources/js/components/users/edituser.vue?vue&type=template&id=db159bf2& ***!
  \***********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_edituser_vue_vue_type_template_id_db159bf2___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   "staticRenderFns": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_edituser_vue_vue_type_template_id_db159bf2___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_edituser_vue_vue_type_template_id_db159bf2___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./edituser.vue?vue&type=template&id=db159bf2& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/users/edituser.vue?vue&type=template&id=db159bf2&");


/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/users/edituser.vue?vue&type=template&id=db159bf2&":
/*!**************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/users/edituser.vue?vue&type=template&id=db159bf2& ***!
  \**************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render),
/* harmony export */   "staticRenderFns": () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function () {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c("div", { staticClass: "col-md-6 grid-margin stretch-card" }, [
    _c("div", { staticClass: "card" }, [
      _c("div", { staticClass: "card-body" }, [
        _c("h4", { staticClass: "card-title" }, [_vm._v("Edit User")]),
        _vm._v(" "),
        _c("p", { staticClass: "card-description" }, [
          _vm._v("Editing user details"),
        ]),
        _vm._v(" "),
        _c(
          "form",
          {
            staticClass: "forms-sample",
            on: {
              submit: function ($event) {
                $event.preventDefault()
                return _vm.updatePost.apply(null, arguments)
              },
            },
          },
          [
            _c("div", { staticClass: "form-group" }, [
              _c("label", { attrs: { for: "firstname" } }, [
                _vm._v("Firstname"),
              ]),
              _vm._v(" "),
              _c("input", {
                directives: [
                  {
                    name: "model",
                    rawName: "v-model",
                    value: _vm.user.firstname,
                    expression: "user.firstname",
                  },
                ],
                staticClass: "form-control",
                attrs: {
                  type: "text",
                  id: "firstname",
                  placeholder: "Firstname",
                },
                domProps: { value: _vm.user.firstname },
                on: {
                  input: function ($event) {
                    if ($event.target.composing) {
                      return
                    }
                    _vm.$set(_vm.user, "firstname", $event.target.value)
                  },
                },
              }),
            ]),
            _vm._v(" "),
            _c("div", { staticClass: "form-group" }, [
              _c("label", { attrs: { for: "firstname" } }, [
                _vm._v("Lastname"),
              ]),
              _vm._v(" "),
              _c("input", {
                directives: [
                  {
                    name: "model",
                    rawName: "v-model",
                    value: _vm.user.lastname,
                    expression: "user.lastname",
                  },
                ],
                staticClass: "form-control",
                attrs: {
                  type: "text",
                  id: "lastname",
                  placeholder: "Lastname",
                },
                domProps: { value: _vm.user.lastname },
                on: {
                  input: function ($event) {
                    if ($event.target.composing) {
                      return
                    }
                    _vm.$set(_vm.user, "lastname", $event.target.value)
                  },
                },
              }),
            ]),
            _vm._v(" "),
            _c("div", { staticClass: "form-group" }, [
              _c("label", { attrs: { for: "email" } }, [
                _vm._v("Email address"),
              ]),
              _vm._v(" "),
              _c("input", {
                directives: [
                  {
                    name: "model",
                    rawName: "v-model",
                    value: _vm.user.email,
                    expression: "user.email",
                  },
                ],
                staticClass: "form-control",
                attrs: { type: "email", id: "email", placeholder: "Email" },
                domProps: { value: _vm.user.email },
                on: {
                  input: function ($event) {
                    if ($event.target.composing) {
                      return
                    }
                    _vm.$set(_vm.user, "email", $event.target.value)
                  },
                },
              }),
            ]),
            _vm._v(" "),
            _c(
              "button",
              {
                staticClass: "btn btn-primary me-2",
                attrs: { type: "submit" },
              },
              [_vm._v("Submit")]
            ),
          ]
        ),
      ]),
    ]),
  ])
}
var staticRenderFns = []
render._withStripped = true



/***/ })

}]);