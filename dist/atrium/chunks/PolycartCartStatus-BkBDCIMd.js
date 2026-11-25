import { d as e } from "./polycart-pC5iCRim.js";
import { UBadge as t } from "@refactor-circus/atrium/ui";
import { computed as n, createBlock as r, defineComponent as i, openBlock as a, unref as o } from "vue";
import { useI18n as s } from "vue-i18n";
// src/components/PolycartCartStatus.vue
var c = /* @__PURE__ */ i({
	__name: "PolycartCartStatus",
	props: {
		status: {},
		expired: { type: Boolean }
	},
	setup(i) {
		let c = i, { t: l } = s(), u = n(() => e(c.status, c.expired)), d = n(() => c.expired ? l("polycart.status_expired", { status: c.status }) : c.status);
		return (e, n) => {
			let s = t;
			return a(), r(s, {
				color: o(u),
				variant: "subtle",
				label: o(d),
				"data-status": i.status,
				"data-color": o(u),
				"data-testid": "polycart-cart-status"
			}, null, 8, [
				"color",
				"label",
				"data-status",
				"data-color"
			]);
		};
	}
});
export { c as t };
