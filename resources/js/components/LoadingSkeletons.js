import { defineComponent, h } from "vue";

const cssSize = (value, fallback) => {
    if (value == null || value === "") return fallback;
    return typeof value === "number" ? `${value}px` : String(value);
};

const createLoader = (name, variant, defaultHeight, lineCount) => defineComponent({
    name,
    // This component already uses Vue 3's h() signature. Opt it out of the
    // Vue 2 render-function compatibility transform.
    compatConfig: { RENDER_FUNCTION: false },
    inheritAttrs: false,
    props: {
        width: { type: [Number, String], default: "100%" },
        height: { type: [Number, String], default: defaultHeight },
    },
    render() {
        return h(
            "div",
            {
                ...this.$attrs,
                class: ["swift-vue-loader", `swift-vue-loader--${variant}`, this.$attrs.class],
                style: {
                    width: cssSize(this.width, "100%"),
                    minHeight: cssSize(this.height, defaultHeight),
                    maxWidth: "100%",
                    ...this.$attrs.style,
                },
                role: "status",
                "aria-label": "Loading",
            },
            Array.from({ length: lineCount }, (_, index) => h("span", { key: index }))
        );
    },
});

export const VclTwitch = createLoader("VclTwitch", "card", "225px", 5);
export const VclTable = createLoader("VclTable", "table", "260px", 8);
