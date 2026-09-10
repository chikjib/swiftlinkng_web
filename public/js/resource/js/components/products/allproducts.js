"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([
    ["resource/js/components/products/allproducts"],
    {
        /***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/modal.vue?vue&type=script&lang=js&":
            /*!************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/modal.vue?vue&type=script&lang=js& ***!
  \************************************************************************************************************************************************************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ default: () =>
                            __WEBPACK_DEFAULT_EXPORT__,
                        /* harmony export */
                    }
                );
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
                /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ =
                    {
                        props: ["show"],
                        mounted: function mounted() {
                            var _this = this;

                            document.addEventListener("keydown", function (e) {
                                if (_this.show && e.keyCode == 27) {
                                    _this.close();
                                }
                            });
                        },
                        methods: {
                            close: function close() {
                                this.$emit("close");
                            },
                        },
                    };

                /***/
            },

        /***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/AddProductModal.vue?vue&type=script&lang=js&":
            /*!*******************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/AddProductModal.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************************************************************************************************************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ default: () =>
                            __WEBPACK_DEFAULT_EXPORT__,
                        /* harmony export */
                    }
                );
                /* harmony import */ var _modal_vue__WEBPACK_IMPORTED_MODULE_0__ =
                    __webpack_require__(
                        /*! ../modal.vue */ "./resources/js/components/modal.vue"
                    );
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

                /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ =
                    {
                        props: ["show"],
                        components: {
                            Modal: _modal_vue__WEBPACK_IMPORTED_MODULE_0__[
                                "default"
                            ],
                        },
                        data: function data() {
                            return {
                                user: this.$store.state.auth.user,
                                product: {},
                                category_id: null,
                                subcategory_id: null,
                                categories: [],
                                // category_id: null,
                                subcategories: [],
                            };
                        },
                        created: function created() {
                            this.getCategories();
                            this.getSubCategories();
                        },
                        methods: {
                            close: function close() {
                                this.$emit("close");
                            },
                            getCategories: function getCategories() {
                                var _this = this;

                                axios
                                    .get("/api/categories", {
                                        headers: {
                                            Authorization: "Bearer ".concat(
                                                token
                                            ),
                                            "Content-Type": "application/json",
                                        },
                                    })
                                    .then(function (response) {
                                        console.log(response.data);
                                        _this.categories = response.data.data; //   console.log(response.data.data.subcategory);
                                        //   this.subcategories = response.data.data.subcategory;
                                    });
                            },
                            filteredSubcategories:
                                function filteredSubcategories() {
                                    var _this2 = this;

                                    return this.subcategories.filter(function (
                                        sub
                                    ) {
                                        return (
                                            sub.category_id ===
                                            _this2.product.category_id
                                        );
                                    });
                                },
                            getSubCategories: function getSubCategories() {
                                var _this3 = this;

                                axios
                                    .get("/api/subcategory", {
                                        headers: {
                                            Authorization: "Bearer ".concat(
                                                token
                                            ),
                                            "Content-Type": "application/json",
                                        },
                                    })
                                    .then(function (response) {
                                        console.log(response.data);
                                        _this3.subcategories =
                                            response.data.data;
                                    });
                            },
                            addProduct: function addProduct() {
                                var _this4 = this;

                                axios
                                    .post("/api/products/", this.product, {
                                        headers: {
                                            Authorization: "Bearer ".concat(
                                                token
                                            ),
                                            "Content-Type": "application/json",
                                        },
                                    })
                                    .then(function (response) {
                                        console.log(response.data);

                                        _this4.close();

                                        _this4.$toasted.show(
                                            response.data.message
                                        ); //this.$router.push({ name: "users" });
                                    })
                                    ["catch"](function (error) {
                                        console.log(error);

                                        _this4.$toasted.show(error);
                                    });
                            },
                        },
                    };

                /***/
            },

        /***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/EditProductModal.vue?vue&type=script&lang=js&":
            /*!********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/EditProductModal.vue?vue&type=script&lang=js& ***!
  \********************************************************************************************************************************************************************************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ default: () =>
                            __WEBPACK_DEFAULT_EXPORT__,
                        /* harmony export */
                    }
                );
                /* harmony import */ var _modal_vue__WEBPACK_IMPORTED_MODULE_0__ =
                    __webpack_require__(
                        /*! ../modal.vue */ "./resources/js/components/modal.vue"
                    );
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

                /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ =
                    {
                        props: ["show", "product"],
                        components: {
                            Modal: _modal_vue__WEBPACK_IMPORTED_MODULE_0__[
                                "default"
                            ],
                        },
                        data: function data() {
                            return {
                                user: this.$store.state.auth.user,
                            };
                        },
                        //   created() {
                        //     axios
                        //       .get(`/api/users/${this.$route.params.id}`, {
                        //         headers: {
                        //           Authorization: `Bearer ${token}`,
                        //           "Content-Type": "application/json",
                        //         },
                        //       })
                        //       .then((response) => {
                        //         console.log(response.data);
                        //         this.user = response.data.data;
                        //       });
                        //   },
                        methods: {
                            close: function close() {
                                this.$emit("close");
                            },
                            updateProduct: function updateProduct() {
                                var _this = this;

                                axios
                                    .put(
                                        "/api/products/".concat(
                                            this.product.id
                                        ),
                                        this.product,
                                        {
                                            headers: {
                                                Authorization: "Bearer ".concat(
                                                    token
                                                ),
                                                "Content-Type":
                                                    "application/json",
                                            },
                                        }
                                    )
                                    .then(function (response) {
                                        console.log(response.data);

                                        _this.close();

                                        _this.$toasted.show(
                                            response.data.message
                                        ); //this.$router.push({ name: "users" });
                                    });
                            },
                        },
                    };

                /***/
            },

        /***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/allproducts.vue?vue&type=script&lang=js&":
            /*!***************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/allproducts.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************************************************************************************************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ default: () =>
                            __WEBPACK_DEFAULT_EXPORT__,
                        /* harmony export */
                    }
                );
                /* harmony import */ var _AddProductModal_vue__WEBPACK_IMPORTED_MODULE_0__ =
                    __webpack_require__(
                        /*! ./AddProductModal.vue */ "./resources/js/components/products/AddProductModal.vue"
                    );
                /* harmony import */ var _EditProductModal_vue__WEBPACK_IMPORTED_MODULE_1__ =
                    __webpack_require__(
                        /*! ./EditProductModal.vue */ "./resources/js/components/products/EditProductModal.vue"
                    );
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
                /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ =
                    {
                        components: {
                            EditProductModal:
                                _EditProductModal_vue__WEBPACK_IMPORTED_MODULE_1__[
                                    "default"
                                ],
                            AddProductModal:
                                _AddProductModal_vue__WEBPACK_IMPORTED_MODULE_0__[
                                    "default"
                                ],
                        },
                        // components: {
                        //   paginate,
                        // },
                        data: function data() {
                            return {
                                products: {},
                                activeModal: 0,
                            };
                        },
                        // created() {
                        //   console.log("token 2" + token);
                        //   axios
                        //     .get("/api/products", {
                        //       headers: {
                        //         Authorization: `Bearer ${token}`,
                        //         "Content-Type": "application/json",
                        //       },
                        //     })
                        //     .then((response) => {
                        //       console.log(response.data);
                        //       this.products = response.data.data;
                        //     });
                        // },
                        mounted: function mounted() {
                            this.list();
                        },
                        methods: {
                            list: function list(page) {
                                var _this = this;

                                if (typeof page === "undefined") {
                                    page = 1;
                                }

                                axios
                                    .get("/api/products?page=".concat(page), {
                                        headers: {
                                            Authorization: "Bearer ".concat(
                                                token
                                            ),
                                            "Content-Type": "application/json",
                                        },
                                    })
                                    .then(function (_ref) {
                                        var data = _ref.data;
                                        _this.products = data;
                                    })
                                    ["catch"](function (_ref2) {
                                        var response = _ref2.response;
                                        console.error(response);
                                    });
                            },
                            deleteProduct: function deleteProduct(id) {
                                var _this2 = this;

                                axios["delete"]("/api/products/".concat(id), {
                                    headers: {
                                        Authorization: "Bearer ".concat(token),
                                        "Content-Type": "application/json",
                                    },
                                }).then(function (response) {
                                    var i = _this2.products.data
                                        .map(function (item) {
                                            return item.id;
                                        })
                                        .indexOf(id); // find index of object

                                    _this2.products.data.splice(i, 1);
                                });
                            },
                            showModal: function showModal(id) {
                                return this.activeModal === id;
                            },
                            toggleModal: function toggleModal(id) {
                                if (this.activeModal !== 0) {
                                    this.activeModal = 0;
                                    return false;
                                }

                                this.activeModal = id;
                            },
                        },
                    };

                /***/
            },

        /***/ "./node_modules/css-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[2]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/modal.vue?vue&type=style&index=0&id=478d961c&scoped=true&lang=css&":
            /*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[2]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/modal.vue?vue&type=style&index=0&id=478d961c&scoped=true&lang=css& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
            /***/ (module, __webpack_exports__, __webpack_require__) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ default: () =>
                            __WEBPACK_DEFAULT_EXPORT__,
                        /* harmony export */
                    }
                );
                /* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__ =
                    __webpack_require__(
                        /*! ../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js"
                    );
                /* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default =
                    /*#__PURE__*/ __webpack_require__.n(
                        _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__
                    );
                // Imports

                var ___CSS_LOADER_EXPORT___ =
                    _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default()(
                        function (i) {
                            return i[1];
                        }
                    );
                // Module
                ___CSS_LOADER_EXPORT___.push([
                    module.id,
                    '\n*[data-v-478d961c] {\n  box-sizing: border-box;\n}\n.modal-mask[data-v-478d961c] {\n  position: fixed;\n  z-index: 9998;\n  top: 0;\n  left: 0;\n  width: 100%;\n  height: 100%;\n  background-color: rgba(0, 0, 0, 0.5);\n  transition: opacity 0.3s ease;\n  overflow-x: auto;\n}\n.modal-container[data-v-478d961c] {\n  width: 40%;\n  height: auto;\n  margin: 40px auto;\n  padding: 20px 30px;\n  background-color: #fff;\n  border-radius: 2px;\n  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);\n  transition: all 0.3s ease;\n}\n.modal-body[data-v-478d961c] {\n  margin: 20px 0;\n}\n/*\n * The following styles are auto-applied to elements with\n * transition="modal" when their visibility is toggled\n * by Vue.js.\n *\n * You can easily play with the modal transition by editing\n * these styles.\n */\n.modal-enter[data-v-478d961c] {\n  opacity: 0;\n}\n.modal-leave-active[data-v-478d961c] {\n  opacity: 0;\n}\n.modal-enter .modal-container[data-v-478d961c],\n.modal-leave-active .modal-container[data-v-478d961c] {\n  transform: scale(1.1);\n}\n.modalclose[data-v-478d961c] {\n  color: red;\n  border-radius: 10px;\n  font-size: 28px;\n  font-weight: 700;\n}\n.close-button[data-v-478d961c] {\n  border: none;\n  display: inline-block;\n  padding: 8px 16px;\n  vertical-align: middle;\n  overflow: hidden;\n  text-decoration: none;\n  color: inherit;\n  background-color: inherit;\n  text-align: center;\n  cursor: pointer;\n  white-space: nowrap;\n}\n.topright[data-v-478d961c] {\n  position: absolute;\n  right: 30%;\n  top: 8%;\n}\n',
                    "",
                ]);
                // Exports
                /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ =
                    ___CSS_LOADER_EXPORT___;

                /***/
            },

        /***/ "./node_modules/css-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[2]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/allproducts.vue?vue&type=style&index=0&id=582fbcb6&scoped=true&lang=css&":
            /*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[2]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/allproducts.vue?vue&type=style&index=0&id=582fbcb6&scoped=true&lang=css& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
            /***/ (module, __webpack_exports__, __webpack_require__) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ default: () =>
                            __WEBPACK_DEFAULT_EXPORT__,
                        /* harmony export */
                    }
                );
                /* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__ =
                    __webpack_require__(
                        /*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js"
                    );
                /* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default =
                    /*#__PURE__*/ __webpack_require__.n(
                        _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__
                    );
                // Imports

                var ___CSS_LOADER_EXPORT___ =
                    _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default()(
                        function (i) {
                            return i[1];
                        }
                    );
                // Module
                ___CSS_LOADER_EXPORT___.push([
                    module.id,
                    "\n.pagination[data-v-582fbcb6] {\n  margin-bottom: 0;\n  margin-top: 5;\n}\n",
                    "",
                ]);
                // Exports
                /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ =
                    ___CSS_LOADER_EXPORT___;

                /***/
            },

        /***/ "./node_modules/css-loader/dist/runtime/api.js":
            /*!*****************************************************!*\
  !*** ./node_modules/css-loader/dist/runtime/api.js ***!
  \*****************************************************/
            /***/ (module) => {
                /*
  MIT License http://www.opensource.org/licenses/mit-license.php
  Author Tobias Koppers @sokra
*/
                // css base code, injected by the css-loader
                // eslint-disable-next-line func-names
                module.exports = function (cssWithMappingToString) {
                    var list = []; // return the list of modules as css string

                    list.toString = function toString() {
                        return this.map(function (item) {
                            var content = cssWithMappingToString(item);

                            if (item[2]) {
                                return "@media "
                                    .concat(item[2], " {")
                                    .concat(content, "}");
                            }

                            return content;
                        }).join("");
                    }; // import a list of modules into the list
                    // eslint-disable-next-line func-names

                    list.i = function (modules, mediaQuery, dedupe) {
                        if (typeof modules === "string") {
                            // eslint-disable-next-line no-param-reassign
                            modules = [[null, modules, ""]];
                        }

                        var alreadyImportedModules = {};

                        if (dedupe) {
                            for (var i = 0; i < this.length; i++) {
                                // eslint-disable-next-line prefer-destructuring
                                var id = this[i][0];

                                if (id != null) {
                                    alreadyImportedModules[id] = true;
                                }
                            }
                        }

                        for (var _i = 0; _i < modules.length; _i++) {
                            var item = [].concat(modules[_i]);

                            if (dedupe && alreadyImportedModules[item[0]]) {
                                // eslint-disable-next-line no-continue
                                continue;
                            }

                            if (mediaQuery) {
                                if (!item[2]) {
                                    item[2] = mediaQuery;
                                } else {
                                    item[2] = ""
                                        .concat(mediaQuery, " and ")
                                        .concat(item[2]);
                                }
                            }

                            list.push(item);
                        }
                    };

                    return list;
                };

                /***/
            },

        /***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[2]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/modal.vue?vue&type=style&index=0&id=478d961c&scoped=true&lang=css&":
            /*!************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[2]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/modal.vue?vue&type=style&index=0&id=478d961c&scoped=true&lang=css& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ default: () =>
                            __WEBPACK_DEFAULT_EXPORT__,
                        /* harmony export */
                    }
                );
                /* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ =
                    __webpack_require__(
                        /*! !../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js"
                    );
                /* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default =
                    /*#__PURE__*/ __webpack_require__.n(
                        _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__
                    );
                /* harmony import */ var _node_modules_css_loader_dist_cjs_js_clonedRuleSet_9_0_rules_0_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_9_0_rules_0_use_2_node_modules_vue_loader_lib_index_js_vue_loader_options_modal_vue_vue_type_style_index_0_id_478d961c_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_1__ =
                    __webpack_require__(
                        /*! !!../../../node_modules/css-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[1]!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[2]!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./modal.vue?vue&type=style&index=0&id=478d961c&scoped=true&lang=css& */ "./node_modules/css-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[2]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/modal.vue?vue&type=style&index=0&id=478d961c&scoped=true&lang=css&"
                    );

                var options = {};

                options.insert = "head";
                options.singleton = false;

                var update =
                    _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(
                        _node_modules_css_loader_dist_cjs_js_clonedRuleSet_9_0_rules_0_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_9_0_rules_0_use_2_node_modules_vue_loader_lib_index_js_vue_loader_options_modal_vue_vue_type_style_index_0_id_478d961c_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_1__[
                            "default"
                        ],
                        options
                    );

                /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ =
                    _node_modules_css_loader_dist_cjs_js_clonedRuleSet_9_0_rules_0_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_9_0_rules_0_use_2_node_modules_vue_loader_lib_index_js_vue_loader_options_modal_vue_vue_type_style_index_0_id_478d961c_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_1__[
                        "default"
                    ].locals || {};

                /***/
            },

        /***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[2]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/allproducts.vue?vue&type=style&index=0&id=582fbcb6&scoped=true&lang=css&":
            /*!***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[2]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/allproducts.vue?vue&type=style&index=0&id=582fbcb6&scoped=true&lang=css& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ default: () =>
                            __WEBPACK_DEFAULT_EXPORT__,
                        /* harmony export */
                    }
                );
                /* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ =
                    __webpack_require__(
                        /*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js"
                    );
                /* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default =
                    /*#__PURE__*/ __webpack_require__.n(
                        _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__
                    );
                /* harmony import */ var _node_modules_css_loader_dist_cjs_js_clonedRuleSet_9_0_rules_0_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_9_0_rules_0_use_2_node_modules_vue_loader_lib_index_js_vue_loader_options_allproducts_vue_vue_type_style_index_0_id_582fbcb6_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_1__ =
                    __webpack_require__(
                        /*! !!../../../../node_modules/css-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[1]!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[2]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./allproducts.vue?vue&type=style&index=0&id=582fbcb6&scoped=true&lang=css& */ "./node_modules/css-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[2]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/allproducts.vue?vue&type=style&index=0&id=582fbcb6&scoped=true&lang=css&"
                    );

                var options = {};

                options.insert = "head";
                options.singleton = false;

                var update =
                    _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(
                        _node_modules_css_loader_dist_cjs_js_clonedRuleSet_9_0_rules_0_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_9_0_rules_0_use_2_node_modules_vue_loader_lib_index_js_vue_loader_options_allproducts_vue_vue_type_style_index_0_id_582fbcb6_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_1__[
                            "default"
                        ],
                        options
                    );

                /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ =
                    _node_modules_css_loader_dist_cjs_js_clonedRuleSet_9_0_rules_0_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_9_0_rules_0_use_2_node_modules_vue_loader_lib_index_js_vue_loader_options_allproducts_vue_vue_type_style_index_0_id_582fbcb6_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_1__[
                        "default"
                    ].locals || {};

                /***/
            },

        /***/ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js":
            /*!****************************************************************************!*\
  !*** ./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js ***!
  \****************************************************************************/
            /***/ (module, __unused_webpack_exports, __webpack_require__) => {
                var isOldIE = (function isOldIE() {
                    var memo;
                    return function memorize() {
                        if (typeof memo === "undefined") {
                            // Test for IE <= 9 as proposed by Browserhacks
                            // @see http://browserhacks.com/#hack-e71d8692f65334173fee715c222cb805
                            // Tests for existence of standard globals is to allow style-loader
                            // to operate correctly into non-standard environments
                            // @see https://github.com/webpack-contrib/style-loader/issues/177
                            memo = Boolean(
                                window &&
                                    document &&
                                    document.all &&
                                    !window.atob
                            );
                        }

                        return memo;
                    };
                })();

                var getTarget = (function getTarget() {
                    var memo = {};
                    return function memorize(target) {
                        if (typeof memo[target] === "undefined") {
                            var styleTarget = document.querySelector(target); // Special case to return head of iframe instead of iframe itself

                            if (
                                window.HTMLIFrameElement &&
                                styleTarget instanceof window.HTMLIFrameElement
                            ) {
                                try {
                                    // This will throw an exception if access to iframe is blocked
                                    // due to cross-origin restrictions
                                    styleTarget =
                                        styleTarget.contentDocument.head;
                                } catch (e) {
                                    // istanbul ignore next
                                    styleTarget = null;
                                }
                            }

                            memo[target] = styleTarget;
                        }

                        return memo[target];
                    };
                })();

                var stylesInDom = [];

                function getIndexByIdentifier(identifier) {
                    var result = -1;

                    for (var i = 0; i < stylesInDom.length; i++) {
                        if (stylesInDom[i].identifier === identifier) {
                            result = i;
                            break;
                        }
                    }

                    return result;
                }

                function modulesToDom(list, options) {
                    var idCountMap = {};
                    var identifiers = [];

                    for (var i = 0; i < list.length; i++) {
                        var item = list[i];
                        var id = options.base
                            ? item[0] + options.base
                            : item[0];
                        var count = idCountMap[id] || 0;
                        var identifier = "".concat(id, " ").concat(count);
                        idCountMap[id] = count + 1;
                        var index = getIndexByIdentifier(identifier);
                        var obj = {
                            css: item[1],
                            media: item[2],
                            sourceMap: item[3],
                        };

                        if (index !== -1) {
                            stylesInDom[index].references++;
                            stylesInDom[index].updater(obj);
                        } else {
                            stylesInDom.push({
                                identifier: identifier,
                                updater: addStyle(obj, options),
                                references: 1,
                            });
                        }

                        identifiers.push(identifier);
                    }

                    return identifiers;
                }

                function insertStyleElement(options) {
                    var style = document.createElement("style");
                    var attributes = options.attributes || {};

                    if (typeof attributes.nonce === "undefined") {
                        var nonce = true ? __webpack_require__.nc : 0;

                        if (nonce) {
                            attributes.nonce = nonce;
                        }
                    }

                    Object.keys(attributes).forEach(function (key) {
                        style.setAttribute(key, attributes[key]);
                    });

                    if (typeof options.insert === "function") {
                        options.insert(style);
                    } else {
                        var target = getTarget(options.insert || "head");

                        if (!target) {
                            throw new Error(
                                "Couldn't find a style target. This probably means that the value for the 'insert' parameter is invalid."
                            );
                        }

                        target.appendChild(style);
                    }

                    return style;
                }

                function removeStyleElement(style) {
                    // istanbul ignore if
                    if (style.parentNode === null) {
                        return false;
                    }

                    style.parentNode.removeChild(style);
                }
                /* istanbul ignore next  */

                var replaceText = (function replaceText() {
                    var textStore = [];
                    return function replace(index, replacement) {
                        textStore[index] = replacement;
                        return textStore.filter(Boolean).join("\n");
                    };
                })();

                function applyToSingletonTag(style, index, remove, obj) {
                    var css = remove
                        ? ""
                        : obj.media
                        ? "@media ".concat(obj.media, " {").concat(obj.css, "}")
                        : obj.css; // For old IE

                    /* istanbul ignore if  */

                    if (style.styleSheet) {
                        style.styleSheet.cssText = replaceText(index, css);
                    } else {
                        var cssNode = document.createTextNode(css);
                        var childNodes = style.childNodes;

                        if (childNodes[index]) {
                            style.removeChild(childNodes[index]);
                        }

                        if (childNodes.length) {
                            style.insertBefore(cssNode, childNodes[index]);
                        } else {
                            style.appendChild(cssNode);
                        }
                    }
                }

                function applyToTag(style, options, obj) {
                    var css = obj.css;
                    var media = obj.media;
                    var sourceMap = obj.sourceMap;

                    if (media) {
                        style.setAttribute("media", media);
                    } else {
                        style.removeAttribute("media");
                    }

                    if (sourceMap && typeof btoa !== "undefined") {
                        css +=
                            "\n/*# sourceMappingURL=data:application/json;base64,".concat(
                                btoa(
                                    unescape(
                                        encodeURIComponent(
                                            JSON.stringify(sourceMap)
                                        )
                                    )
                                ),
                                " */"
                            );
                    } // For old IE

                    /* istanbul ignore if  */

                    if (style.styleSheet) {
                        style.styleSheet.cssText = css;
                    } else {
                        while (style.firstChild) {
                            style.removeChild(style.firstChild);
                        }

                        style.appendChild(document.createTextNode(css));
                    }
                }

                var singleton = null;
                var singletonCounter = 0;

                function addStyle(obj, options) {
                    var style;
                    var update;
                    var remove;

                    if (options.singleton) {
                        var styleIndex = singletonCounter++;
                        style =
                            singleton ||
                            (singleton = insertStyleElement(options));
                        update = applyToSingletonTag.bind(
                            null,
                            style,
                            styleIndex,
                            false
                        );
                        remove = applyToSingletonTag.bind(
                            null,
                            style,
                            styleIndex,
                            true
                        );
                    } else {
                        style = insertStyleElement(options);
                        update = applyToTag.bind(null, style, options);

                        remove = function remove() {
                            removeStyleElement(style);
                        };
                    }

                    update(obj);
                    return function updateStyle(newObj) {
                        if (newObj) {
                            if (
                                newObj.css === obj.css &&
                                newObj.media === obj.media &&
                                newObj.sourceMap === obj.sourceMap
                            ) {
                                return;
                            }

                            update((obj = newObj));
                        } else {
                            remove();
                        }
                    };
                }

                module.exports = function (list, options) {
                    options = options || {}; // Force single-tag solution on IE6-9, which has a hard limit on the # of <style>
                    // tags it will allow on a page

                    if (
                        !options.singleton &&
                        typeof options.singleton !== "boolean"
                    ) {
                        options.singleton = isOldIE();
                    }

                    list = list || [];
                    var lastIdentifiers = modulesToDom(list, options);
                    return function update(newList) {
                        newList = newList || [];

                        if (
                            Object.prototype.toString.call(newList) !==
                            "[object Array]"
                        ) {
                            return;
                        }

                        for (var i = 0; i < lastIdentifiers.length; i++) {
                            var identifier = lastIdentifiers[i];
                            var index = getIndexByIdentifier(identifier);
                            stylesInDom[index].references--;
                        }

                        var newLastIdentifiers = modulesToDom(newList, options);

                        for (var _i = 0; _i < lastIdentifiers.length; _i++) {
                            var _identifier = lastIdentifiers[_i];

                            var _index = getIndexByIdentifier(_identifier);

                            if (stylesInDom[_index].references === 0) {
                                stylesInDom[_index].updater();

                                stylesInDom.splice(_index, 1);
                            }
                        }

                        lastIdentifiers = newLastIdentifiers;
                    };
                };

                /***/
            },

        /***/ "./resources/js/components/modal.vue":
            /*!*******************************************!*\
  !*** ./resources/js/components/modal.vue ***!
  \*******************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ default: () =>
                            __WEBPACK_DEFAULT_EXPORT__,
                        /* harmony export */
                    }
                );
                /* harmony import */ var _modal_vue_vue_type_template_id_478d961c_scoped_true___WEBPACK_IMPORTED_MODULE_0__ =
                    __webpack_require__(
                        /*! ./modal.vue?vue&type=template&id=478d961c&scoped=true& */ "./resources/js/components/modal.vue?vue&type=template&id=478d961c&scoped=true&"
                    );
                /* harmony import */ var _modal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ =
                    __webpack_require__(
                        /*! ./modal.vue?vue&type=script&lang=js& */ "./resources/js/components/modal.vue?vue&type=script&lang=js&"
                    );
                /* harmony import */ var _modal_vue_vue_type_style_index_0_id_478d961c_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_2__ =
                    __webpack_require__(
                        /*! ./modal.vue?vue&type=style&index=0&id=478d961c&scoped=true&lang=css& */ "./resources/js/components/modal.vue?vue&type=style&index=0&id=478d961c&scoped=true&lang=css&"
                    );
                /* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ =
                    __webpack_require__(
                        /*! !../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js"
                    );

                /* normalize component */

                var component = (0,
                _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__[
                    "default"
                ])(
                    _modal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[
                        "default"
                    ],
                    _modal_vue_vue_type_template_id_478d961c_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
                    _modal_vue_vue_type_template_id_478d961c_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
                    false,
                    null,
                    "478d961c",
                    null
                );

                /* hot reload */
                if (false) {
                    var api;
                }
                component.options.__file = "resources/js/components/modal.vue";
                /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ =
                    component.exports;

                /***/
            },

        /***/ "./resources/js/components/products/AddProductModal.vue":
            /*!**************************************************************!*\
  !*** ./resources/js/components/products/AddProductModal.vue ***!
  \**************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ default: () =>
                            __WEBPACK_DEFAULT_EXPORT__,
                        /* harmony export */
                    }
                );
                /* harmony import */ var _AddProductModal_vue_vue_type_template_id_6ccafcbf___WEBPACK_IMPORTED_MODULE_0__ =
                    __webpack_require__(
                        /*! ./AddProductModal.vue?vue&type=template&id=6ccafcbf& */ "./resources/js/components/products/AddProductModal.vue?vue&type=template&id=6ccafcbf&"
                    );
                /* harmony import */ var _AddProductModal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ =
                    __webpack_require__(
                        /*! ./AddProductModal.vue?vue&type=script&lang=js& */ "./resources/js/components/products/AddProductModal.vue?vue&type=script&lang=js&"
                    );
                /* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ =
                    __webpack_require__(
                        /*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js"
                    );

                /* normalize component */
                var component = (0,
                _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__[
                    "default"
                ])(
                    _AddProductModal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[
                        "default"
                    ],
                    _AddProductModal_vue_vue_type_template_id_6ccafcbf___WEBPACK_IMPORTED_MODULE_0__.render,
                    _AddProductModal_vue_vue_type_template_id_6ccafcbf___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
                    false,
                    null,
                    null,
                    null
                );

                /* hot reload */
                if (false) {
                    var api;
                }
                component.options.__file =
                    "resources/js/components/products/AddProductModal.vue";
                /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ =
                    component.exports;

                /***/
            },

        /***/ "./resources/js/components/products/EditProductModal.vue":
            /*!***************************************************************!*\
  !*** ./resources/js/components/products/EditProductModal.vue ***!
  \***************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ default: () =>
                            __WEBPACK_DEFAULT_EXPORT__,
                        /* harmony export */
                    }
                );
                /* harmony import */ var _EditProductModal_vue_vue_type_template_id_232a2658___WEBPACK_IMPORTED_MODULE_0__ =
                    __webpack_require__(
                        /*! ./EditProductModal.vue?vue&type=template&id=232a2658& */ "./resources/js/components/products/EditProductModal.vue?vue&type=template&id=232a2658&"
                    );
                /* harmony import */ var _EditProductModal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ =
                    __webpack_require__(
                        /*! ./EditProductModal.vue?vue&type=script&lang=js& */ "./resources/js/components/products/EditProductModal.vue?vue&type=script&lang=js&"
                    );
                /* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ =
                    __webpack_require__(
                        /*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js"
                    );

                /* normalize component */
                var component = (0,
                _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__[
                    "default"
                ])(
                    _EditProductModal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[
                        "default"
                    ],
                    _EditProductModal_vue_vue_type_template_id_232a2658___WEBPACK_IMPORTED_MODULE_0__.render,
                    _EditProductModal_vue_vue_type_template_id_232a2658___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
                    false,
                    null,
                    null,
                    null
                );

                /* hot reload */
                if (false) {
                    var api;
                }
                component.options.__file =
                    "resources/js/components/products/EditProductModal.vue";
                /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ =
                    component.exports;

                /***/
            },

        /***/ "./resources/js/components/products/allproducts.vue":
            /*!**********************************************************!*\
  !*** ./resources/js/components/products/allproducts.vue ***!
  \**********************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ default: () =>
                            __WEBPACK_DEFAULT_EXPORT__,
                        /* harmony export */
                    }
                );
                /* harmony import */ var _allproducts_vue_vue_type_template_id_582fbcb6_scoped_true___WEBPACK_IMPORTED_MODULE_0__ =
                    __webpack_require__(
                        /*! ./allproducts.vue?vue&type=template&id=582fbcb6&scoped=true& */ "./resources/js/components/products/allproducts.vue?vue&type=template&id=582fbcb6&scoped=true&"
                    );
                /* harmony import */ var _allproducts_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ =
                    __webpack_require__(
                        /*! ./allproducts.vue?vue&type=script&lang=js& */ "./resources/js/components/products/allproducts.vue?vue&type=script&lang=js&"
                    );
                /* harmony import */ var _allproducts_vue_vue_type_style_index_0_id_582fbcb6_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_2__ =
                    __webpack_require__(
                        /*! ./allproducts.vue?vue&type=style&index=0&id=582fbcb6&scoped=true&lang=css& */ "./resources/js/components/products/allproducts.vue?vue&type=style&index=0&id=582fbcb6&scoped=true&lang=css&"
                    );
                /* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ =
                    __webpack_require__(
                        /*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js"
                    );

                /* normalize component */

                var component = (0,
                _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__[
                    "default"
                ])(
                    _allproducts_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[
                        "default"
                    ],
                    _allproducts_vue_vue_type_template_id_582fbcb6_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
                    _allproducts_vue_vue_type_template_id_582fbcb6_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
                    false,
                    null,
                    "582fbcb6",
                    null
                );

                /* hot reload */
                if (false) {
                    var api;
                }
                component.options.__file =
                    "resources/js/components/products/allproducts.vue";
                /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ =
                    component.exports;

                /***/
            },

        /***/ "./resources/js/components/modal.vue?vue&type=script&lang=js&":
            /*!********************************************************************!*\
  !*** ./resources/js/components/modal.vue?vue&type=script&lang=js& ***!
  \********************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ default: () =>
                            __WEBPACK_DEFAULT_EXPORT__,
                        /* harmony export */
                    }
                );
                /* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_modal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ =
                    __webpack_require__(
                        /*! -!../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./modal.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/modal.vue?vue&type=script&lang=js&"
                    );
                /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ =
                    _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_modal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[
                        "default"
                    ];

                /***/
            },

        /***/ "./resources/js/components/products/AddProductModal.vue?vue&type=script&lang=js&":
            /*!***************************************************************************************!*\
  !*** ./resources/js/components/products/AddProductModal.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ default: () =>
                            __WEBPACK_DEFAULT_EXPORT__,
                        /* harmony export */
                    }
                );
                /* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_AddProductModal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ =
                    __webpack_require__(
                        /*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AddProductModal.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/AddProductModal.vue?vue&type=script&lang=js&"
                    );
                /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ =
                    _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_AddProductModal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[
                        "default"
                    ];

                /***/
            },

        /***/ "./resources/js/components/products/EditProductModal.vue?vue&type=script&lang=js&":
            /*!****************************************************************************************!*\
  !*** ./resources/js/components/products/EditProductModal.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ default: () =>
                            __WEBPACK_DEFAULT_EXPORT__,
                        /* harmony export */
                    }
                );
                /* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProductModal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ =
                    __webpack_require__(
                        /*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./EditProductModal.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/EditProductModal.vue?vue&type=script&lang=js&"
                    );
                /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ =
                    _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProductModal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[
                        "default"
                    ];

                /***/
            },

        /***/ "./resources/js/components/products/allproducts.vue?vue&type=script&lang=js&":
            /*!***********************************************************************************!*\
  !*** ./resources/js/components/products/allproducts.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ default: () =>
                            __WEBPACK_DEFAULT_EXPORT__,
                        /* harmony export */
                    }
                );
                /* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_allproducts_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ =
                    __webpack_require__(
                        /*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./allproducts.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/allproducts.vue?vue&type=script&lang=js&"
                    );
                /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ =
                    _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_allproducts_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[
                        "default"
                    ];

                /***/
            },

        /***/ "./resources/js/components/modal.vue?vue&type=style&index=0&id=478d961c&scoped=true&lang=css&":
            /*!****************************************************************************************************!*\
  !*** ./resources/js/components/modal.vue?vue&type=style&index=0&id=478d961c&scoped=true&lang=css& ***!
  \****************************************************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_clonedRuleSet_9_0_rules_0_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_9_0_rules_0_use_2_node_modules_vue_loader_lib_index_js_vue_loader_options_modal_vue_vue_type_style_index_0_id_478d961c_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__ =
                    __webpack_require__(
                        /*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[1]!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[2]!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./modal.vue?vue&type=style&index=0&id=478d961c&scoped=true&lang=css& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[2]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/modal.vue?vue&type=style&index=0&id=478d961c&scoped=true&lang=css&"
                    );

                /***/
            },

        /***/ "./resources/js/components/products/allproducts.vue?vue&type=style&index=0&id=582fbcb6&scoped=true&lang=css&":
            /*!*******************************************************************************************************************!*\
  !*** ./resources/js/components/products/allproducts.vue?vue&type=style&index=0&id=582fbcb6&scoped=true&lang=css& ***!
  \*******************************************************************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_clonedRuleSet_9_0_rules_0_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_9_0_rules_0_use_2_node_modules_vue_loader_lib_index_js_vue_loader_options_allproducts_vue_vue_type_style_index_0_id_582fbcb6_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__ =
                    __webpack_require__(
                        /*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[1]!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[2]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./allproducts.vue?vue&type=style&index=0&id=582fbcb6&scoped=true&lang=css& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9[0].rules[0].use[2]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/allproducts.vue?vue&type=style&index=0&id=582fbcb6&scoped=true&lang=css&"
                    );

                /***/
            },

        /***/ "./resources/js/components/modal.vue?vue&type=template&id=478d961c&scoped=true&":
            /*!**************************************************************************************!*\
  !*** ./resources/js/components/modal.vue?vue&type=template&id=478d961c&scoped=true& ***!
  \**************************************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ render: () =>
                            /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_modal_vue_vue_type_template_id_478d961c_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
                        /* harmony export */ staticRenderFns: () =>
                            /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_modal_vue_vue_type_template_id_478d961c_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
                        /* harmony export */
                    }
                );
                /* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_modal_vue_vue_type_template_id_478d961c_scoped_true___WEBPACK_IMPORTED_MODULE_0__ =
                    __webpack_require__(
                        /*! -!../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./modal.vue?vue&type=template&id=478d961c&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/modal.vue?vue&type=template&id=478d961c&scoped=true&"
                    );

                /***/
            },

        /***/ "./resources/js/components/products/AddProductModal.vue?vue&type=template&id=6ccafcbf&":
            /*!*********************************************************************************************!*\
  !*** ./resources/js/components/products/AddProductModal.vue?vue&type=template&id=6ccafcbf& ***!
  \*********************************************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ render: () =>
                            /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_AddProductModal_vue_vue_type_template_id_6ccafcbf___WEBPACK_IMPORTED_MODULE_0__.render,
                        /* harmony export */ staticRenderFns: () =>
                            /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_AddProductModal_vue_vue_type_template_id_6ccafcbf___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
                        /* harmony export */
                    }
                );
                /* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_AddProductModal_vue_vue_type_template_id_6ccafcbf___WEBPACK_IMPORTED_MODULE_0__ =
                    __webpack_require__(
                        /*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AddProductModal.vue?vue&type=template&id=6ccafcbf& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/AddProductModal.vue?vue&type=template&id=6ccafcbf&"
                    );

                /***/
            },

        /***/ "./resources/js/components/products/EditProductModal.vue?vue&type=template&id=232a2658&":
            /*!**********************************************************************************************!*\
  !*** ./resources/js/components/products/EditProductModal.vue?vue&type=template&id=232a2658& ***!
  \**********************************************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ render: () =>
                            /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProductModal_vue_vue_type_template_id_232a2658___WEBPACK_IMPORTED_MODULE_0__.render,
                        /* harmony export */ staticRenderFns: () =>
                            /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProductModal_vue_vue_type_template_id_232a2658___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
                        /* harmony export */
                    }
                );
                /* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProductModal_vue_vue_type_template_id_232a2658___WEBPACK_IMPORTED_MODULE_0__ =
                    __webpack_require__(
                        /*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./EditProductModal.vue?vue&type=template&id=232a2658& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/EditProductModal.vue?vue&type=template&id=232a2658&"
                    );

                /***/
            },

        /***/ "./resources/js/components/products/allproducts.vue?vue&type=template&id=582fbcb6&scoped=true&":
            /*!*****************************************************************************************************!*\
  !*** ./resources/js/components/products/allproducts.vue?vue&type=template&id=582fbcb6&scoped=true& ***!
  \*****************************************************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ render: () =>
                            /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_allproducts_vue_vue_type_template_id_582fbcb6_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
                        /* harmony export */ staticRenderFns: () =>
                            /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_allproducts_vue_vue_type_template_id_582fbcb6_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
                        /* harmony export */
                    }
                );
                /* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_allproducts_vue_vue_type_template_id_582fbcb6_scoped_true___WEBPACK_IMPORTED_MODULE_0__ =
                    __webpack_require__(
                        /*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./allproducts.vue?vue&type=template&id=582fbcb6&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/allproducts.vue?vue&type=template&id=582fbcb6&scoped=true&"
                    );

                /***/
            },

        /***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/modal.vue?vue&type=template&id=478d961c&scoped=true&":
            /*!*****************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/modal.vue?vue&type=template&id=478d961c&scoped=true& ***!
  \*****************************************************************************************************************************************************************************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ render: () => /* binding */ render,
                        /* harmony export */ staticRenderFns: () =>
                            /* binding */ staticRenderFns,
                        /* harmony export */
                    }
                );
                var render = function () {
                    var _vm = this;
                    var _h = _vm.$createElement;
                    var _c = _vm._self._c || _h;
                    return _c("transition", { attrs: { name: "modal" } }, [
                        _c(
                            "div",
                            {
                                directives: [
                                    {
                                        name: "show",
                                        rawName: "v-show",
                                        value: _vm.show,
                                        expression: "show",
                                    },
                                ],
                                staticClass: "modal-mask",
                            },
                            [
                                _c(
                                    "div",
                                    { staticClass: "modal-container" },
                                    [
                                        _c(
                                            "div",
                                            { staticClass: "modalclose" },
                                            [
                                                _c(
                                                    "span",
                                                    {
                                                        staticClass:
                                                            "close-button topright",
                                                        on: {
                                                            click: function (
                                                                $event
                                                            ) {
                                                                $event.stopPropagation();
                                                                return _vm.close.apply(
                                                                    null,
                                                                    arguments
                                                                );
                                                            },
                                                        },
                                                    },
                                                    [_vm._v("×")]
                                                ),
                                            ]
                                        ),
                                        _vm._v(" "),
                                        _vm._t("default"),
                                    ],
                                    2
                                ),
                            ]
                        ),
                    ]);
                };
                var staticRenderFns = [];
                render._withStripped = true;

                /***/
            },

        /***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/AddProductModal.vue?vue&type=template&id=6ccafcbf&":
            /*!************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/AddProductModal.vue?vue&type=template&id=6ccafcbf& ***!
  \************************************************************************************************************************************************************************************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ render: () => /* binding */ render,
                        /* harmony export */ staticRenderFns: () =>
                            /* binding */ staticRenderFns,
                        /* harmony export */
                    }
                );
                var render = function () {
                    var _vm = this;
                    var _h = _vm.$createElement;
                    var _c = _vm._self._c || _h;
                    return _c(
                        "div",
                        [
                            _c(
                                "modal",
                                {
                                    attrs: { show: _vm.show },
                                    on: { close: _vm.close },
                                },
                                [
                                    _c("div", { staticClass: "card" }, [
                                        _c(
                                            "div",
                                            { staticClass: "card-body" },
                                            [
                                                _c(
                                                    "h4",
                                                    {
                                                        staticClass:
                                                            "card-title",
                                                    },
                                                    [_vm._v("Add Product")]
                                                ),
                                                _vm._v(" "),
                                                _c(
                                                    "p",
                                                    {
                                                        staticClass:
                                                            "card-description",
                                                    },
                                                    [
                                                        _vm._v(
                                                            "Adding new Product"
                                                        ),
                                                    ]
                                                ),
                                                _vm._v(" "),
                                                _c(
                                                    "form",
                                                    {
                                                        staticClass:
                                                            "forms-sample",
                                                        on: {
                                                            submit: function (
                                                                $event
                                                            ) {
                                                                $event.preventDefault();
                                                                return _vm.addProduct.apply(
                                                                    null,
                                                                    arguments
                                                                );
                                                            },
                                                        },
                                                    },
                                                    [
                                                        _c(
                                                            "div",
                                                            {
                                                                staticClass:
                                                                    "form-group",
                                                            },
                                                            [
                                                                _c(
                                                                    "label",
                                                                    {
                                                                        attrs: {
                                                                            for: "category",
                                                                        },
                                                                    },
                                                                    [
                                                                        _vm._v(
                                                                            "Category"
                                                                        ),
                                                                    ]
                                                                ),
                                                                _vm._v(" "),
                                                                _c(
                                                                    "select",
                                                                    {
                                                                        directives:
                                                                            [
                                                                                {
                                                                                    name: "model",
                                                                                    rawName:
                                                                                        "v-model",
                                                                                    value: _vm
                                                                                        .product
                                                                                        .category_id,
                                                                                    expression:
                                                                                        "product.category_id",
                                                                                },
                                                                            ],
                                                                        staticClass:
                                                                            "form-control",
                                                                        attrs: {
                                                                            name: "category_id",
                                                                            "form-control-sm":
                                                                                "",
                                                                        },
                                                                        on: {
                                                                            change: [
                                                                                function (
                                                                                    $event
                                                                                ) {
                                                                                    var $$selectedVal =
                                                                                        Array.prototype.filter
                                                                                            .call(
                                                                                                $event
                                                                                                    .target
                                                                                                    .options,
                                                                                                function (
                                                                                                    o
                                                                                                ) {
                                                                                                    return o.selected;
                                                                                                }
                                                                                            )
                                                                                            .map(
                                                                                                function (
                                                                                                    o
                                                                                                ) {
                                                                                                    var val =
                                                                                                        "_value" in
                                                                                                        o
                                                                                                            ? o._value
                                                                                                            : o.value;
                                                                                                    return val;
                                                                                                }
                                                                                            );
                                                                                    _vm.$set(
                                                                                        _vm.product,
                                                                                        "category_id",
                                                                                        $event
                                                                                            .target
                                                                                            .multiple
                                                                                            ? $$selectedVal
                                                                                            : $$selectedVal[0]
                                                                                    );
                                                                                },
                                                                                function (
                                                                                    $event
                                                                                ) {
                                                                                    return _vm.filteredSubcategories();
                                                                                },
                                                                            ],
                                                                        },
                                                                    },
                                                                    [
                                                                        _c(
                                                                            "option",
                                                                            [
                                                                                _vm._v(
                                                                                    "Select Category"
                                                                                ),
                                                                            ]
                                                                        ),
                                                                        _vm._v(
                                                                            " "
                                                                        ),
                                                                        _vm._l(
                                                                            _vm.categories,
                                                                            function (
                                                                                category
                                                                            ) {
                                                                                return _c(
                                                                                    "option",
                                                                                    {
                                                                                        key: category.id,
                                                                                        domProps:
                                                                                            {
                                                                                                value: category.id,
                                                                                            },
                                                                                    },
                                                                                    [
                                                                                        _vm._v(
                                                                                            "\n                " +
                                                                                                _vm._s(
                                                                                                    category.title
                                                                                                ) +
                                                                                                "\n              "
                                                                                        ),
                                                                                    ]
                                                                                );
                                                                            }
                                                                        ),
                                                                    ],
                                                                    2
                                                                ),
                                                            ]
                                                        ),
                                                        _vm._v(" "),
                                                        _c(
                                                            "div",
                                                            {
                                                                staticClass:
                                                                    "form-group",
                                                            },
                                                            [
                                                                _c(
                                                                    "label",
                                                                    {
                                                                        attrs: {
                                                                            for: "subcategory",
                                                                        },
                                                                    },
                                                                    [
                                                                        _vm._v(
                                                                            "SubCategory"
                                                                        ),
                                                                    ]
                                                                ),
                                                                _vm._v(" "),
                                                                _c(
                                                                    "select",
                                                                    {
                                                                        directives:
                                                                            [
                                                                                {
                                                                                    name: "model",
                                                                                    rawName:
                                                                                        "v-model",
                                                                                    value: _vm
                                                                                        .product
                                                                                        .subcategory_id,
                                                                                    expression:
                                                                                        "product.subcategory_id",
                                                                                },
                                                                            ],
                                                                        staticClass:
                                                                            "form-control",
                                                                        attrs: {
                                                                            name: "subcategory_id",
                                                                            "form-control-sm":
                                                                                "",
                                                                        },
                                                                        on: {
                                                                            change: function (
                                                                                $event
                                                                            ) {
                                                                                var $$selectedVal =
                                                                                    Array.prototype.filter
                                                                                        .call(
                                                                                            $event
                                                                                                .target
                                                                                                .options,
                                                                                            function (
                                                                                                o
                                                                                            ) {
                                                                                                return o.selected;
                                                                                            }
                                                                                        )
                                                                                        .map(
                                                                                            function (
                                                                                                o
                                                                                            ) {
                                                                                                var val =
                                                                                                    "_value" in
                                                                                                    o
                                                                                                        ? o._value
                                                                                                        : o.value;
                                                                                                return val;
                                                                                            }
                                                                                        );
                                                                                _vm.$set(
                                                                                    _vm.product,
                                                                                    "subcategory_id",
                                                                                    $event
                                                                                        .target
                                                                                        .multiple
                                                                                        ? $$selectedVal
                                                                                        : $$selectedVal[0]
                                                                                );
                                                                            },
                                                                        },
                                                                    },
                                                                    _vm._l(
                                                                        _vm.filteredSubcategories(),
                                                                        function (
                                                                            item
                                                                        ) {
                                                                            return _c(
                                                                                "option",
                                                                                {
                                                                                    key: item.id,
                                                                                    domProps:
                                                                                        {
                                                                                            value: item.id,
                                                                                        },
                                                                                },
                                                                                [
                                                                                    _vm._v(
                                                                                        "\n                " +
                                                                                            _vm._s(
                                                                                                item.title
                                                                                            ) +
                                                                                            "\n              "
                                                                                    ),
                                                                                ]
                                                                            );
                                                                        }
                                                                    ),
                                                                    0
                                                                ),
                                                            ]
                                                        ),
                                                        _vm._v(" "),
                                                        _c(
                                                            "div",
                                                            {
                                                                staticClass:
                                                                    "form-group",
                                                            },
                                                            [
                                                                _c(
                                                                    "label",
                                                                    {
                                                                        attrs: {
                                                                            for: "productname",
                                                                        },
                                                                    },
                                                                    [
                                                                        _vm._v(
                                                                            "Product Name"
                                                                        ),
                                                                    ]
                                                                ),
                                                                _vm._v(" "),
                                                                _c("input", {
                                                                    directives:
                                                                        [
                                                                            {
                                                                                name: "model",
                                                                                rawName:
                                                                                    "v-model",
                                                                                value: _vm
                                                                                    .product
                                                                                    .product_name,
                                                                                expression:
                                                                                    "product.product_name",
                                                                            },
                                                                        ],
                                                                    staticClass:
                                                                        "form-control",
                                                                    attrs: {
                                                                        type: "text",
                                                                        id: "productname",
                                                                        placeholder:
                                                                            "Product Name",
                                                                    },
                                                                    domProps: {
                                                                        value: _vm
                                                                            .product
                                                                            .product_name,
                                                                    },
                                                                    on: {
                                                                        input: function (
                                                                            $event
                                                                        ) {
                                                                            if (
                                                                                $event
                                                                                    .target
                                                                                    .composing
                                                                            ) {
                                                                                return;
                                                                            }
                                                                            _vm.$set(
                                                                                _vm.product,
                                                                                "product_name",
                                                                                $event
                                                                                    .target
                                                                                    .value
                                                                            );
                                                                        },
                                                                    },
                                                                }),
                                                            ]
                                                        ),
                                                        _vm._v(" "),
                                                        _c(
                                                            "div",
                                                            {
                                                                staticClass:
                                                                    "form-group",
                                                            },
                                                            [
                                                                _c(
                                                                    "label",
                                                                    {
                                                                        attrs: {
                                                                            for: "Description",
                                                                        },
                                                                    },
                                                                    [
                                                                        _vm._v(
                                                                            "Description"
                                                                        ),
                                                                    ]
                                                                ),
                                                                _vm._v(" "),
                                                                _c("input", {
                                                                    directives:
                                                                        [
                                                                            {
                                                                                name: "model",
                                                                                rawName:
                                                                                    "v-model",
                                                                                value: _vm
                                                                                    .product
                                                                                    .description,
                                                                                expression:
                                                                                    "product.description",
                                                                            },
                                                                        ],
                                                                    staticClass:
                                                                        "form-control",
                                                                    attrs: {
                                                                        type: "text",
                                                                        id: "description",
                                                                        placeholder:
                                                                            "Description",
                                                                    },
                                                                    domProps: {
                                                                        value: _vm
                                                                            .product
                                                                            .description,
                                                                    },
                                                                    on: {
                                                                        input: function (
                                                                            $event
                                                                        ) {
                                                                            if (
                                                                                $event
                                                                                    .target
                                                                                    .composing
                                                                            ) {
                                                                                return;
                                                                            }
                                                                            _vm.$set(
                                                                                _vm.product,
                                                                                "description",
                                                                                $event
                                                                                    .target
                                                                                    .value
                                                                            );
                                                                        },
                                                                    },
                                                                }),
                                                            ]
                                                        ),
                                                        _vm._v(" "),
                                                        _c(
                                                            "div",
                                                            {
                                                                staticClass:
                                                                    "form-group",
                                                            },
                                                            [
                                                                _c(
                                                                    "label",
                                                                    {
                                                                        attrs: {
                                                                            for: "Price",
                                                                        },
                                                                    },
                                                                    [
                                                                        _vm._v(
                                                                            "Price"
                                                                        ),
                                                                    ]
                                                                ),
                                                                _vm._v(" "),
                                                                _c("input", {
                                                                    directives:
                                                                        [
                                                                            {
                                                                                name: "model",
                                                                                rawName:
                                                                                    "v-model",
                                                                                value: _vm
                                                                                    .product
                                                                                    .price,
                                                                                expression:
                                                                                    "product.price",
                                                                            },
                                                                        ],
                                                                    staticClass:
                                                                        "form-control",
                                                                    attrs: {
                                                                        type: "number",
                                                                        id: "price",
                                                                        placeholder:
                                                                            "Price",
                                                                    },
                                                                    domProps: {
                                                                        value: _vm
                                                                            .product
                                                                            .price,
                                                                    },
                                                                    on: {
                                                                        input: function (
                                                                            $event
                                                                        ) {
                                                                            if (
                                                                                $event
                                                                                    .target
                                                                                    .composing
                                                                            ) {
                                                                                return;
                                                                            }
                                                                            _vm.$set(
                                                                                _vm.product,
                                                                                "price",
                                                                                $event
                                                                                    .target
                                                                                    .value
                                                                            );
                                                                        },
                                                                    },
                                                                }),
                                                            ]
                                                        ),
                                                        _vm._v(" "),
                                                        _c(
                                                            "div",
                                                            {
                                                                staticClass:
                                                                    "form-group",
                                                            },
                                                            [
                                                                _c(
                                                                    "label",
                                                                    {
                                                                        attrs: {
                                                                            for: "stock",
                                                                        },
                                                                    },
                                                                    [
                                                                        _vm._v(
                                                                            "Stock"
                                                                        ),
                                                                    ]
                                                                ),
                                                                _vm._v(" "),
                                                                _c("input", {
                                                                    directives:
                                                                        [
                                                                            {
                                                                                name: "model",
                                                                                rawName:
                                                                                    "v-model",
                                                                                value: _vm
                                                                                    .product
                                                                                    .stock,
                                                                                expression:
                                                                                    "product.stock",
                                                                            },
                                                                        ],
                                                                    staticClass:
                                                                        "form-control",
                                                                    attrs: {
                                                                        type: "text",
                                                                        id: "stock",
                                                                        placeholder:
                                                                            "Stock",
                                                                    },
                                                                    domProps: {
                                                                        value: _vm
                                                                            .product
                                                                            .stock,
                                                                    },
                                                                    on: {
                                                                        input: function (
                                                                            $event
                                                                        ) {
                                                                            if (
                                                                                $event
                                                                                    .target
                                                                                    .composing
                                                                            ) {
                                                                                return;
                                                                            }
                                                                            _vm.$set(
                                                                                _vm.product,
                                                                                "stock",
                                                                                $event
                                                                                    .target
                                                                                    .value
                                                                            );
                                                                        },
                                                                    },
                                                                }),
                                                            ]
                                                        ),
                                                        _vm._v(" "),
                                                        _c(
                                                            "div",
                                                            {
                                                                staticClass:
                                                                    "form-group",
                                                            },
                                                            [
                                                                _c(
                                                                    "label",
                                                                    {
                                                                        attrs: {
                                                                            for: "min_qty",
                                                                        },
                                                                    },
                                                                    [
                                                                        _vm._v(
                                                                            "Min Qty"
                                                                        ),
                                                                    ]
                                                                ),
                                                                _vm._v(" "),
                                                                _c("input", {
                                                                    directives:
                                                                        [
                                                                            {
                                                                                name: "model",
                                                                                rawName:
                                                                                    "v-model",
                                                                                value: _vm
                                                                                    .product
                                                                                    .min_qty,
                                                                                expression:
                                                                                    "product.min_qty",
                                                                            },
                                                                        ],
                                                                    staticClass:
                                                                        "form-control",
                                                                    attrs: {
                                                                        type: "number",
                                                                        id: "min_qty",
                                                                        placeholder:
                                                                            "Minimun Order Quantity",
                                                                    },
                                                                    domProps: {
                                                                        value: _vm
                                                                            .product
                                                                            .min_qty,
                                                                    },
                                                                    on: {
                                                                        input: function (
                                                                            $event
                                                                        ) {
                                                                            if (
                                                                                $event
                                                                                    .target
                                                                                    .composing
                                                                            ) {
                                                                                return;
                                                                            }
                                                                            _vm.$set(
                                                                                _vm.product,
                                                                                "min_qty",
                                                                                $event
                                                                                    .target
                                                                                    .value
                                                                            );
                                                                        },
                                                                    },
                                                                }),
                                                            ]
                                                        ),
                                                        _vm._v(" "),
                                                        _c(
                                                            "button",
                                                            {
                                                                staticClass:
                                                                    "btn btn-primary me-2",
                                                                attrs: {
                                                                    type: "submit",
                                                                },
                                                            },
                                                            [_vm._v("Submit")]
                                                        ),
                                                    ]
                                                ),
                                            ]
                                        ),
                                    ]),
                                ]
                            ),
                        ],
                        1
                    );
                };
                var staticRenderFns = [];
                render._withStripped = true;

                /***/
            },

        /***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/EditProductModal.vue?vue&type=template&id=232a2658&":
            /*!*************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/EditProductModal.vue?vue&type=template&id=232a2658& ***!
  \*************************************************************************************************************************************************************************************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ render: () => /* binding */ render,
                        /* harmony export */ staticRenderFns: () =>
                            /* binding */ staticRenderFns,
                        /* harmony export */
                    }
                );
                var render = function () {
                    var _vm = this;
                    var _h = _vm.$createElement;
                    var _c = _vm._self._c || _h;
                    return _c(
                        "div",
                        [
                            _c(
                                "modal",
                                {
                                    attrs: { show: _vm.show },
                                    on: { close: _vm.close },
                                },
                                [
                                    _c("div", { staticClass: "card" }, [
                                        _c(
                                            "div",
                                            { staticClass: "card-body" },
                                            [
                                                _c(
                                                    "h4",
                                                    {
                                                        staticClass:
                                                            "card-title",
                                                    },
                                                    [_vm._v("Edit Product")]
                                                ),
                                                _vm._v(" "),
                                                _c(
                                                    "p",
                                                    {
                                                        staticClass:
                                                            "card-description",
                                                    },
                                                    [_vm._v("Editing Product")]
                                                ),
                                                _vm._v(" "),
                                                _c(
                                                    "form",
                                                    {
                                                        staticClass:
                                                            "forms-sample",
                                                        on: {
                                                            submit: function (
                                                                $event
                                                            ) {
                                                                $event.preventDefault();
                                                                return _vm.updateProduct.apply(
                                                                    null,
                                                                    arguments
                                                                );
                                                            },
                                                        },
                                                    },
                                                    [
                                                        _c(
                                                            "div",
                                                            {
                                                                staticClass:
                                                                    "form-group",
                                                            },
                                                            [
                                                                _c(
                                                                    "label",
                                                                    {
                                                                        attrs: {
                                                                            for: "productname",
                                                                        },
                                                                    },
                                                                    [
                                                                        _vm._v(
                                                                            "Product Name"
                                                                        ),
                                                                    ]
                                                                ),
                                                                _vm._v(" "),
                                                                _c("input", {
                                                                    directives:
                                                                        [
                                                                            {
                                                                                name: "model",
                                                                                rawName:
                                                                                    "v-model",
                                                                                value: _vm
                                                                                    .product
                                                                                    .product_name,
                                                                                expression:
                                                                                    "product.product_name",
                                                                            },
                                                                        ],
                                                                    staticClass:
                                                                        "form-control",
                                                                    attrs: {
                                                                        type: "text",
                                                                        id: "productname",
                                                                        placeholder:
                                                                            "Product Name",
                                                                    },
                                                                    domProps: {
                                                                        value: _vm
                                                                            .product
                                                                            .product_name,
                                                                    },
                                                                    on: {
                                                                        input: function (
                                                                            $event
                                                                        ) {
                                                                            if (
                                                                                $event
                                                                                    .target
                                                                                    .composing
                                                                            ) {
                                                                                return;
                                                                            }
                                                                            _vm.$set(
                                                                                _vm.product,
                                                                                "product_name",
                                                                                $event
                                                                                    .target
                                                                                    .value
                                                                            );
                                                                        },
                                                                    },
                                                                }),
                                                            ]
                                                        ),
                                                        _vm._v(" "),
                                                        _c(
                                                            "div",
                                                            {
                                                                staticClass:
                                                                    "form-group",
                                                            },
                                                            [
                                                                _c(
                                                                    "label",
                                                                    {
                                                                        attrs: {
                                                                            for: "Description",
                                                                        },
                                                                    },
                                                                    [
                                                                        _vm._v(
                                                                            "Description"
                                                                        ),
                                                                    ]
                                                                ),
                                                                _vm._v(" "),
                                                                _c("input", {
                                                                    directives:
                                                                        [
                                                                            {
                                                                                name: "model",
                                                                                rawName:
                                                                                    "v-model",
                                                                                value: _vm
                                                                                    .product
                                                                                    .description,
                                                                                expression:
                                                                                    "product.description",
                                                                            },
                                                                        ],
                                                                    staticClass:
                                                                        "form-control",
                                                                    attrs: {
                                                                        type: "text",
                                                                        id: "description",
                                                                        placeholder:
                                                                            "Description",
                                                                    },
                                                                    domProps: {
                                                                        value: _vm
                                                                            .product
                                                                            .description,
                                                                    },
                                                                    on: {
                                                                        input: function (
                                                                            $event
                                                                        ) {
                                                                            if (
                                                                                $event
                                                                                    .target
                                                                                    .composing
                                                                            ) {
                                                                                return;
                                                                            }
                                                                            _vm.$set(
                                                                                _vm.product,
                                                                                "description",
                                                                                $event
                                                                                    .target
                                                                                    .value
                                                                            );
                                                                        },
                                                                    },
                                                                }),
                                                            ]
                                                        ),
                                                        _vm._v(" "),
                                                        _c(
                                                            "div",
                                                            {
                                                                staticClass:
                                                                    "form-group",
                                                            },
                                                            [
                                                                _c(
                                                                    "label",
                                                                    {
                                                                        attrs: {
                                                                            for: "Price",
                                                                        },
                                                                    },
                                                                    [
                                                                        _vm._v(
                                                                            "Price"
                                                                        ),
                                                                    ]
                                                                ),
                                                                _vm._v(" "),
                                                                _c("input", {
                                                                    directives:
                                                                        [
                                                                            {
                                                                                name: "model",
                                                                                rawName:
                                                                                    "v-model",
                                                                                value: _vm
                                                                                    .product
                                                                                    .price,
                                                                                expression:
                                                                                    "product.price",
                                                                            },
                                                                        ],
                                                                    staticClass:
                                                                        "form-control",
                                                                    attrs: {
                                                                        type: "number",
                                                                        id: "price",
                                                                        placeholder:
                                                                            "Price",
                                                                    },
                                                                    domProps: {
                                                                        value: _vm
                                                                            .product
                                                                            .price,
                                                                    },
                                                                    on: {
                                                                        input: function (
                                                                            $event
                                                                        ) {
                                                                            if (
                                                                                $event
                                                                                    .target
                                                                                    .composing
                                                                            ) {
                                                                                return;
                                                                            }
                                                                            _vm.$set(
                                                                                _vm.product,
                                                                                "price",
                                                                                $event
                                                                                    .target
                                                                                    .value
                                                                            );
                                                                        },
                                                                    },
                                                                }),
                                                            ]
                                                        ),
                                                        _vm._v(" "),
                                                        _c(
                                                            "div",
                                                            {
                                                                staticClass:
                                                                    "form-group",
                                                            },
                                                            [
                                                                _c(
                                                                    "label",
                                                                    {
                                                                        attrs: {
                                                                            for: "stock",
                                                                        },
                                                                    },
                                                                    [
                                                                        _vm._v(
                                                                            "Stock"
                                                                        ),
                                                                    ]
                                                                ),
                                                                _vm._v(" "),
                                                                _c("input", {
                                                                    directives:
                                                                        [
                                                                            {
                                                                                name: "model",
                                                                                rawName:
                                                                                    "v-model",
                                                                                value: _vm
                                                                                    .product
                                                                                    .stock,
                                                                                expression:
                                                                                    "product.stock",
                                                                            },
                                                                        ],
                                                                    staticClass:
                                                                        "form-control",
                                                                    attrs: {
                                                                        type: "text",
                                                                        id: "stock",
                                                                        placeholder:
                                                                            "Stock",
                                                                    },
                                                                    domProps: {
                                                                        value: _vm
                                                                            .product
                                                                            .stock,
                                                                    },
                                                                    on: {
                                                                        input: function (
                                                                            $event
                                                                        ) {
                                                                            if (
                                                                                $event
                                                                                    .target
                                                                                    .composing
                                                                            ) {
                                                                                return;
                                                                            }
                                                                            _vm.$set(
                                                                                _vm.product,
                                                                                "stock",
                                                                                $event
                                                                                    .target
                                                                                    .value
                                                                            );
                                                                        },
                                                                    },
                                                                }),
                                                            ]
                                                        ),
                                                        _vm._v(" "),
                                                        _c(
                                                            "div",
                                                            {
                                                                staticClass:
                                                                    "form-group",
                                                            },
                                                            [
                                                                _c(
                                                                    "label",
                                                                    {
                                                                        attrs: {
                                                                            for: "min_qty",
                                                                        },
                                                                    },
                                                                    [
                                                                        _vm._v(
                                                                            "Min Qty"
                                                                        ),
                                                                    ]
                                                                ),
                                                                _vm._v(" "),
                                                                _c("input", {
                                                                    directives:
                                                                        [
                                                                            {
                                                                                name: "model",
                                                                                rawName:
                                                                                    "v-model",
                                                                                value: _vm
                                                                                    .product
                                                                                    .min_qty,
                                                                                expression:
                                                                                    "product.min_qty",
                                                                            },
                                                                        ],
                                                                    staticClass:
                                                                        "form-control",
                                                                    attrs: {
                                                                        type: "number",
                                                                        id: "min_qty",
                                                                        placeholder:
                                                                            "Minimun Order Quantity",
                                                                    },
                                                                    domProps: {
                                                                        value: _vm
                                                                            .product
                                                                            .min_qty,
                                                                    },
                                                                    on: {
                                                                        input: function (
                                                                            $event
                                                                        ) {
                                                                            if (
                                                                                $event
                                                                                    .target
                                                                                    .composing
                                                                            ) {
                                                                                return;
                                                                            }
                                                                            _vm.$set(
                                                                                _vm.product,
                                                                                "min_qty",
                                                                                $event
                                                                                    .target
                                                                                    .value
                                                                            );
                                                                        },
                                                                    },
                                                                }),
                                                            ]
                                                        ),
                                                        _vm._v(" "),
                                                        _c("input", {
                                                            directives: [
                                                                {
                                                                    name: "model",
                                                                    rawName:
                                                                        "v-model",
                                                                    value: _vm
                                                                        .user
                                                                        .id,
                                                                    expression:
                                                                        "user.id",
                                                                },
                                                            ],
                                                            staticClass:
                                                                "form-control",
                                                            attrs: {
                                                                type: "hidden",
                                                                id: "user_id",
                                                            },
                                                            domProps: {
                                                                value: _vm.user
                                                                    .id,
                                                            },
                                                            on: {
                                                                input: function (
                                                                    $event
                                                                ) {
                                                                    if (
                                                                        $event
                                                                            .target
                                                                            .composing
                                                                    ) {
                                                                        return;
                                                                    }
                                                                    _vm.$set(
                                                                        _vm.user,
                                                                        "id",
                                                                        $event
                                                                            .target
                                                                            .value
                                                                    );
                                                                },
                                                            },
                                                        }),
                                                        _vm._v(" "),
                                                        _c(
                                                            "button",
                                                            {
                                                                staticClass:
                                                                    "btn btn-primary me-2",
                                                                attrs: {
                                                                    type: "submit",
                                                                },
                                                            },
                                                            [_vm._v("Submit")]
                                                        ),
                                                    ]
                                                ),
                                            ]
                                        ),
                                    ]),
                                ]
                            ),
                        ],
                        1
                    );
                };
                var staticRenderFns = [];
                render._withStripped = true;

                /***/
            },

        /***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/allproducts.vue?vue&type=template&id=582fbcb6&scoped=true&":
            /*!********************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/products/allproducts.vue?vue&type=template&id=582fbcb6&scoped=true& ***!
  \********************************************************************************************************************************************************************************************************************************************/
            /***/ (
                __unused_webpack_module,
                __webpack_exports__,
                __webpack_require__
            ) => {
                __webpack_require__.r(__webpack_exports__);
                /* harmony export */ __webpack_require__.d(
                    __webpack_exports__,
                    {
                        /* harmony export */ render: () => /* binding */ render,
                        /* harmony export */ staticRenderFns: () =>
                            /* binding */ staticRenderFns,
                        /* harmony export */
                    }
                );
                var render = function () {
                    var _vm = this;
                    var _h = _vm.$createElement;
                    var _c = _vm._self._c || _h;
                    return _c(
                        "div",
                        { staticClass: "col-lg-12 grid-margin stretch-card" },
                        [
                            _c("div", { staticClass: "card" }, [
                                _c(
                                    "div",
                                    { staticClass: "card-body" },
                                    [
                                        _c(
                                            "h4",
                                            { staticClass: "card-title" },
                                            [_vm._v("All Products")]
                                        ),
                                        _vm._v(" "),
                                        _c(
                                            "button",
                                            {
                                                staticClass:
                                                    "btn btn-primary btn-rounded btn-fw",
                                                attrs: {
                                                    type: "button",
                                                    href: "#",
                                                },
                                                on: {
                                                    click: function ($event) {
                                                        $event.stopPropagation();
                                                        return _vm.toggleModal(
                                                            "addproduct"
                                                        );
                                                    },
                                                },
                                            },
                                            [
                                                _vm._v(
                                                    "\n        Add Product\n      "
                                                ),
                                            ]
                                        ),
                                        _vm._v(" "),
                                        _c("add-product-modal", {
                                            attrs: {
                                                show: _vm.showModal(
                                                    "addproduct"
                                                ),
                                            },
                                            on: {
                                                close: function ($event) {
                                                    return _vm.toggleModal(
                                                        "addproduct"
                                                    );
                                                },
                                            },
                                        }),
                                        _vm._v(" "),
                                        _c(
                                            "p",
                                            { staticClass: "card-description" },
                                            [_vm._v("Product Listing")]
                                        ),
                                        _vm._v(" "),
                                        _c(
                                            "div",
                                            {
                                                staticClass:
                                                    "table-responsive pt-3",
                                            },
                                            [
                                                _c(
                                                    "table",
                                                    {
                                                        staticClass:
                                                            "table table-bordered",
                                                    },
                                                    [
                                                        _vm._m(0),
                                                        _vm._v(" "),
                                                        _c(
                                                            "tbody",
                                                            _vm._l(
                                                                _vm.products
                                                                    .data,
                                                                function (
                                                                    product
                                                                ) {
                                                                    return _c(
                                                                        "tr",
                                                                        {
                                                                            key: product.id,
                                                                        },
                                                                        [
                                                                            _c(
                                                                                "td",
                                                                                [
                                                                                    _vm._v(
                                                                                        _vm._s(
                                                                                            product.id
                                                                                        )
                                                                                    ),
                                                                                ]
                                                                            ),
                                                                            _vm._v(
                                                                                " "
                                                                            ),
                                                                            _c(
                                                                                "td",
                                                                                [
                                                                                    _vm._v(
                                                                                        _vm._s(
                                                                                            product
                                                                                                .cat
                                                                                                .title
                                                                                        )
                                                                                    ),
                                                                                ]
                                                                            ),
                                                                            _vm._v(
                                                                                " "
                                                                            ),
                                                                            _c(
                                                                                "td",
                                                                                [
                                                                                    _vm._v(
                                                                                        _vm._s(
                                                                                            product
                                                                                                .subcat
                                                                                                .title
                                                                                        )
                                                                                    ),
                                                                                ]
                                                                            ),
                                                                            _vm._v(
                                                                                " "
                                                                            ),
                                                                            _c(
                                                                                "td",
                                                                                [
                                                                                    _vm._v(
                                                                                        _vm._s(
                                                                                            product.product_name
                                                                                        )
                                                                                    ),
                                                                                ]
                                                                            ),
                                                                            _vm._v(
                                                                                " "
                                                                            ),
                                                                            _c(
                                                                                "td",
                                                                                [
                                                                                    _vm._v(
                                                                                        _vm._s(
                                                                                            product.description
                                                                                        )
                                                                                    ),
                                                                                ]
                                                                            ),
                                                                            _vm._v(
                                                                                " "
                                                                            ),
                                                                            _c(
                                                                                "td",
                                                                                [
                                                                                    _vm._v(
                                                                                        "₦ " +
                                                                                            _vm._s(
                                                                                                product.price
                                                                                            )
                                                                                    ),
                                                                                ]
                                                                            ),
                                                                            _vm._v(
                                                                                " "
                                                                            ),
                                                                            _c(
                                                                                "td",
                                                                                [
                                                                                    _vm._v(
                                                                                        _vm._s(
                                                                                            product.stock
                                                                                        )
                                                                                    ),
                                                                                ]
                                                                            ),
                                                                            _vm._v(
                                                                                " "
                                                                            ),
                                                                            _c(
                                                                                "td",
                                                                                [
                                                                                    _vm._v(
                                                                                        _vm._s(
                                                                                            product.min_qty
                                                                                        )
                                                                                    ),
                                                                                ]
                                                                            ),
                                                                            _vm._v(
                                                                                " "
                                                                            ),
                                                                            _c(
                                                                                "td",
                                                                                [
                                                                                    _vm._v(
                                                                                        _vm._s(
                                                                                            product.created_at
                                                                                        )
                                                                                    ),
                                                                                ]
                                                                            ),
                                                                            _vm._v(
                                                                                " "
                                                                            ),
                                                                            _c(
                                                                                "td",
                                                                                [
                                                                                    _c(
                                                                                        "div",
                                                                                        {
                                                                                            staticClass:
                                                                                                "btn-group",
                                                                                            attrs: {
                                                                                                role: "group",
                                                                                            },
                                                                                        },
                                                                                        [
                                                                                            _c(
                                                                                                "a",
                                                                                                {
                                                                                                    staticClass:
                                                                                                        "btn btn-primary",
                                                                                                    attrs: {
                                                                                                        href: "#",
                                                                                                    },
                                                                                                    on: {
                                                                                                        click: function (
                                                                                                            $event
                                                                                                        ) {
                                                                                                            $event.stopPropagation();
                                                                                                            return _vm.toggleModal(
                                                                                                                product.id
                                                                                                            );
                                                                                                        },
                                                                                                    },
                                                                                                },
                                                                                                [
                                                                                                    _vm._v(
                                                                                                        "Edit"
                                                                                                    ),
                                                                                                ]
                                                                                            ),
                                                                                            _vm._v(
                                                                                                " "
                                                                                            ),
                                                                                            _c(
                                                                                                "edit-product-modal",
                                                                                                {
                                                                                                    attrs: {
                                                                                                        show: _vm.showModal(
                                                                                                            product.id
                                                                                                        ),
                                                                                                        product:
                                                                                                            product,
                                                                                                    },
                                                                                                    on: {
                                                                                                        close: function (
                                                                                                            $event
                                                                                                        ) {
                                                                                                            return _vm.toggleModal(
                                                                                                                product.id
                                                                                                            );
                                                                                                        },
                                                                                                    },
                                                                                                }
                                                                                            ),
                                                                                            _vm._v(
                                                                                                " "
                                                                                            ),
                                                                                            _c(
                                                                                                "button",
                                                                                                {
                                                                                                    staticClass:
                                                                                                        "btn btn-danger",
                                                                                                    on: {
                                                                                                        click: function (
                                                                                                            $event
                                                                                                        ) {
                                                                                                            return _vm.deleteProduct(
                                                                                                                product.id
                                                                                                            );
                                                                                                        },
                                                                                                    },
                                                                                                },
                                                                                                [
                                                                                                    _vm._v(
                                                                                                        "\n                    Delete\n                  "
                                                                                                    ),
                                                                                                ]
                                                                                            ),
                                                                                        ],
                                                                                        1
                                                                                    ),
                                                                                ]
                                                                            ),
                                                                        ]
                                                                    );
                                                                }
                                                            ),
                                                            0
                                                        ),
                                                    ]
                                                ),
                                            ]
                                        ),
                                        _vm._v(" "),
                                        _c("pagination", {
                                            attrs: {
                                                align: "center",
                                                data: _vm.products,
                                            },
                                            on: {
                                                "pagination-change-page":
                                                    _vm.list,
                                            },
                                        }),
                                    ],
                                    1
                                ),
                            ]),
                        ]
                    );
                };
                var staticRenderFns = [
                    function () {
                        var _vm = this;
                        var _h = _vm.$createElement;
                        var _c = _vm._self._c || _h;
                        return _c("thead", [
                            _c("tr", [
                                _c("th", [_vm._v("ID")]),
                                _vm._v(" "),
                                _c("th", [_vm._v("Category")]),
                                _vm._v(" "),
                                _c("th", [_vm._v("Subcategory")]),
                                _vm._v(" "),
                                _c("th", [_vm._v("Product Name")]),
                                _vm._v(" "),
                                _c("th", [_vm._v("Description")]),
                                _vm._v(" "),
                                _c("th", [_vm._v("Price")]),
                                _vm._v(" "),
                                _c("th", [_vm._v("Stock")]),
                                _vm._v(" "),
                                _c("th", [_vm._v("Min Qty")]),
                                _vm._v(" "),
                                _c("th", [_vm._v("Created At")]),
                                _vm._v(" "),
                                _c("th", [_vm._v("Actions")]),
                            ]),
                        ]);
                    },
                ];
                render._withStripped = true;

                /***/
            },
    },
]);
