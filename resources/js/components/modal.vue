<template>
  <transition name="modal">
    <div class="modal-mask" v-show="show">
      <div class="modal-container">
        <div class="modalclose">
          <span @click.stop="close" class="close-button topright">&times;</span>
        </div>
        <slot></slot>
      </div>
    </div>
  </transition>
</template>

<script>
export default {
  props: ["show"],

  mounted: function () {
    document.addEventListener("keydown", (e) => {
      if (this.show && e.keyCode == 27) {
        this.close();
      }
    });
  },
  methods: {
    close: function () {
      this.$emit("close");
    },
  },
};
</script>

<style scoped>
* {
  box-sizing: border-box;
}
.modal-mask {
  position: fixed;
  z-index: 9998;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  transition: opacity 0.3s ease;
  overflow-x: auto;
}
.modal-container {
  width: 70%;
  height: auto;
  margin: 40px auto;
  padding: 20px 30px;
  background-color: #fff;
  border-radius: 2px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
  transition: all 0.3s ease;
}
.modal-body {
  margin: 20px 0;
}
/*
 * The following styles are auto-applied to elements with
 * transition="modal" when their visibility is toggled
 * by Vue.js.
 *
 * You can easily play with the modal transition by editing
 * these styles.
 */
.modal-enter {
  opacity: 0;
}
.modal-leave-active {
  opacity: 0;
}
.modal-enter .modal-container,
.modal-leave-active .modal-container {
  -webkit-transform: scale(1.1);
  transform: scale(1.1);
}
.modalclose {
  color: red;
  border-radius: 10px;
  font-size: 28px;
  font-weight: 700;
}
.close-button {
  border: none;
  display: inline-block;
  padding: 8px 16px;
  vertical-align: middle;
  overflow: hidden;
  text-decoration: none;
  color: inherit;
  background-color: inherit;
  text-align: center;
  cursor: pointer;
  white-space: nowrap;
}

.topright {
  position: absolute;
  right: 15%;
  top: 4%;
}
</style>