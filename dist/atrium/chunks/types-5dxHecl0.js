import { a as e, d as t, f as n } from "./polycart-pC5iCRim.js";
import { UBadge as r, UButton as i, UDashboardNavbar as a, UDashboardPanel as o, USkeleton as s } from "@refactor-circus/atrium/ui";
import { AtriumIntegrationScreen as c, AtriumNoAccess as l, atriumStatus as u, useHead as d } from "@refactor-circus/atrium";
import { Fragment as f, createBlock as p, createCommentVNode as m, createElementBlock as h, createElementVNode as g, createTextVNode as _, createVNode as v, defineComponent as y, openBlock as b, ref as x, renderList as S, resolveComponent as C, shallowRef as w, toDisplayString as T, unref as E, withCtx as D } from "vue";
import { useI18n as O } from "vue-i18n";
// src/components/PolycartTypes.vue?vue&type=script&setup=true&lang.ts
var k = {
	class: "flex flex-col gap-4",
	"data-testid": "polycart-types"
}, A = {
	key: 2,
	class: "flex items-center justify-between gap-3 rounded-md border border-default p-4 text-sm",
	role: "alert",
	"data-testid": "polycart-load-error"
}, j = {
	key: 3,
	class: "overflow-x-auto rounded-md border border-default"
}, M = {
	class: "w-full text-left text-sm",
	"data-testid": "polycart-types-table"
}, N = { class: "bg-elevated text-muted" }, P = { class: "px-3 py-2 font-medium" }, F = { class: "px-3 py-2 font-medium" }, I = { class: "px-3 py-2 font-medium" }, L = { class: "px-3 py-2 font-medium" }, R = { class: "px-3 py-2 font-medium" }, z = { class: "px-3 py-2 font-medium" }, B = { class: "divide-y divide-default" }, V = { class: "px-3 py-2" }, H = { class: "px-3 py-2" }, U = {
	key: 0,
	class: "flex flex-wrap items-center gap-2"
}, W = {
	key: 1,
	class: "text-muted"
}, G = { class: "px-3 py-2" }, K = { class: "px-3 py-2" }, q = { class: "px-3 py-2" }, J = { class: "flex flex-wrap gap-1" }, Y = { class: "px-3 py-2" }, X = /* @__PURE__ */ y({
	__name: "PolycartTypes",
	setup(a) {
		let { t: o } = O(), c = n(), d = w([]), y = x(!1), X = x(!1), Z = x(!1);
		async function Q() {
			if (!c) y.value = !0;
			else try {
				d.value = e(await c.GET("/types")).data, X.value = !1;
			} catch (e) {
				Z.value = u(e) === 403, X.value = !Z.value;
			} finally {
				y.value = !0;
			}
		}
		function $(e) {
			return {
				path: "/polycart/carts",
				query: { type: e }
			};
		}
		return Q(), (e, n) => {
			let a = l, c = s, u = i, x = C("RouterLink"), w = r;
			return b(), h("div", k, [E(Z) ? (b(), p(a, { key: 0 })) : E(y) ? E(X) ? (b(), h("div", A, [g("span", null, T(E(o)("polycart.types.load_failed")), 1), v(u, {
				size: "sm",
				variant: "outline",
				icon: "i-lucide-refresh-cw",
				label: E(o)("polycart.retry"),
				"data-testid": "polycart-retry",
				onClick: n[0] ||= (e) => Q()
			}, null, 8, ["label"])])) : (b(), h("div", j, [g("table", M, [g("thead", N, [g("tr", null, [
				g("th", P, T(E(o)("polycart.types.type")), 1),
				g("th", F, T(E(o)("polycart.types.statuses")), 1),
				g("th", I, T(E(o)("polycart.types.converts_to")), 1),
				g("th", L, T(E(o)("polycart.types.parents")), 1),
				g("th", R, T(E(o)("polycart.types.rules")), 1),
				g("th", z, T(E(o)("polycart.types.lifetime")), 1)
			])]), g("tbody", B, [(b(!0), h(f, null, S(E(d), (e) => (b(), h("tr", {
				key: e.key,
				"data-testid": "polycart-type"
			}, [
				g("td", V, [v(x, {
					to: $(e.key),
					class: "font-medium underline-offset-2 hover:underline",
					"data-testid": "polycart-type-link"
				}, {
					default: D(() => [_(T(e.key), 1)]),
					_: 2
				}, 1032, ["to"])]),
				g("td", H, [e.statuses.length > 0 ? (b(), h("div", U, [(b(!0), h(f, null, S(e.statuses, (e) => (b(), p(w, {
					key: e,
					color: E(t)(e),
					variant: "subtle",
					label: e,
					"data-status": e,
					"data-testid": "polycart-type-status"
				}, null, 8, [
					"color",
					"label",
					"data-status"
				]))), 128))])) : (b(), h("span", W, "—"))]),
				g("td", G, T(e.converts_to.join(", ") || "—"), 1),
				g("td", K, [e.parents === null ? (b(), h(f, { key: 0 }, [_(T(E(o)("polycart.types.any")), 1)], 64)) : e.parents.length === 0 ? (b(), h(f, { key: 1 }, [_(T(E(o)("polycart.types.root_only")), 1)], 64)) : (b(), h(f, { key: 2 }, [_(T(e.parents.join(", ")), 1)], 64))]),
				g("td", q, [g("div", J, [
					e.requires_price ? (b(), p(w, {
						key: 0,
						color: "warning",
						variant: "subtle",
						label: E(o)("polycart.types.requires_price")
					}, null, 8, ["label"])) : m("", !0),
					e.requires_parent ? (b(), p(w, {
						key: 1,
						color: "primary",
						variant: "subtle",
						label: E(o)("polycart.types.requires_parent")
					}, null, 8, ["label"])) : m("", !0),
					e.merges_lines ? m("", !0) : (b(), p(w, {
						key: 2,
						color: "neutral",
						variant: "subtle",
						label: E(o)("polycart.types.separate_lines")
					}, null, 8, ["label"]))
				])]),
				g("td", Y, T(e.lifetime ?? E(o)("polycart.never")), 1)
			]))), 128))])])])) : (b(), p(c, {
				key: 1,
				class: "h-32"
			}))]);
		};
	}
}), Z = { class: "mb-4 text-sm text-muted" }, Q = /* @__PURE__ */ y({
	__name: "types",
	setup(e) {
		let { t } = O();
		return d({ title: () => t("polycart.types.title") }), (e, n) => {
			let r = a, i = c, s = o;
			return b(), p(s, null, {
				header: D(() => [v(r, { title: E(t)("polycart.types.title") }, null, 8, ["title"])]),
				body: D(() => [g("p", Z, T(E(t)("polycart.types.description")), 1), v(i, { "screen-key": "polycart.types" }, {
					default: D(() => [v(X)]),
					_: 1
				})]),
				_: 1
			});
		};
	}
});
export { Q as default };
