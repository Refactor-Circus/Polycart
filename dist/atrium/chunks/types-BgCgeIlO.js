import { a as e, d as t, f as n } from "./polycart-pC5iCRim.js";
import { UBadge as r, UButton as i, UDashboardNavbar as a, UDashboardPanel as o, ULink as s, USkeleton as c, UTable as l } from "@refactor-circus/atrium/ui";
import { AtriumIntegrationScreen as u, AtriumNoAccess as d, AtriumState as f, atriumStatus as p, useHead as m } from "@refactor-circus/atrium";
import { Fragment as h, computed as g, createBlock as _, createCommentVNode as v, createElementBlock as y, createElementVNode as b, createTextVNode as x, createVNode as S, defineComponent as C, openBlock as w, ref as T, renderList as E, shallowRef as D, toDisplayString as O, unref as k, withCtx as A } from "vue";
import { useI18n as j } from "vue-i18n";
// src/components/PolycartTypes.vue?vue&type=script&setup=true&lang.ts
var M = {
	class: "flex flex-col gap-4",
	"data-testid": "polycart-types"
}, N = {
	key: 0,
	class: "flex flex-wrap items-center gap-2"
}, P = {
	key: 1,
	class: "text-muted"
}, F = { class: "flex flex-wrap gap-1" }, I = /* @__PURE__ */ C({
	__name: "PolycartTypes",
	setup(a) {
		let { t: o } = j(), u = n(), m = D([]), C = T(!1), I = T(!1), L = T(!1);
		async function R() {
			if (!u) C.value = !0;
			else try {
				m.value = e(await u.GET("/types")).data, I.value = !1;
			} catch (e) {
				L.value = p(e) === 403, I.value = !L.value;
			} finally {
				C.value = !0;
			}
		}
		let z = g(() => [
			{
				id: "type",
				header: o("polycart.types.type")
			},
			{
				id: "statuses",
				header: o("polycart.types.statuses")
			},
			{
				id: "converts_to",
				header: o("polycart.types.converts_to")
			},
			{
				id: "parents",
				header: o("polycart.types.parents")
			},
			{
				id: "rules",
				header: o("polycart.types.rules")
			},
			{
				id: "lifetime",
				header: o("polycart.types.lifetime")
			}
		]);
		function B(e) {
			return {
				path: "/polycart/carts",
				query: { type: e }
			};
		}
		return R(), (e, n) => {
			let a = d, u = c, p = i, g = f, T = s, D = r, j = l;
			return w(), y("div", M, [k(L) ? (w(), _(a, { key: 0 })) : k(C) ? k(I) ? (w(), _(g, {
				key: 2,
				kind: "error",
				title: k(o)("polycart.types.load_failed"),
				"data-testid": "polycart-load-error"
			}, {
				default: A(() => [S(p, {
					size: "sm",
					variant: "outline",
					icon: "i-lucide-refresh-cw",
					label: k(o)("polycart.retry"),
					"data-testid": "polycart-retry",
					onClick: n[0] ||= (e) => R()
				}, null, 8, ["label"])]),
				_: 1
			}, 8, ["title"])) : (w(), _(j, {
				key: 3,
				data: k(m),
				columns: k(z),
				class: "rounded-md border border-default",
				"data-testid": "polycart-types-table"
			}, {
				"type-cell": A(({ row: e }) => [S(T, {
					to: B(e.original.key),
					class: "font-medium underline-offset-2 hover:underline",
					"data-testid": "polycart-type-link"
				}, {
					default: A(() => [x(O(e.original.key), 1)]),
					_: 2
				}, 1032, ["to"])]),
				"statuses-cell": A(({ row: e }) => [e.original.statuses.length > 0 ? (w(), y("div", N, [(w(!0), y(h, null, E(e.original.statuses, (e) => (w(), _(D, {
					key: e,
					color: k(t)(e),
					variant: "subtle",
					label: e,
					"data-status": e,
					"data-testid": "polycart-type-status"
				}, null, 8, [
					"color",
					"label",
					"data-status"
				]))), 128))])) : (w(), y("span", P, "—"))]),
				"converts_to-cell": A(({ row: e }) => [x(O(e.original.converts_to.join(", ") || "—"), 1)]),
				"parents-cell": A(({ row: e }) => [e.original.parents === null ? (w(), y(h, { key: 0 }, [x(O(k(o)("polycart.types.any")), 1)], 64)) : e.original.parents.length === 0 ? (w(), y(h, { key: 1 }, [x(O(k(o)("polycart.types.root_only")), 1)], 64)) : (w(), y(h, { key: 2 }, [x(O(e.original.parents.join(", ")), 1)], 64))]),
				"rules-cell": A(({ row: e }) => [b("div", F, [
					e.original.requires_price ? (w(), _(D, {
						key: 0,
						color: "warning",
						variant: "subtle",
						label: k(o)("polycart.types.requires_price")
					}, null, 8, ["label"])) : v("", !0),
					e.original.requires_parent ? (w(), _(D, {
						key: 1,
						color: "primary",
						variant: "subtle",
						label: k(o)("polycart.types.requires_parent")
					}, null, 8, ["label"])) : v("", !0),
					e.original.merges_lines ? v("", !0) : (w(), _(D, {
						key: 2,
						color: "neutral",
						variant: "subtle",
						label: k(o)("polycart.types.separate_lines")
					}, null, 8, ["label"]))
				])]),
				"lifetime-cell": A(({ row: e }) => [x(O(e.original.lifetime ?? k(o)("polycart.never")), 1)]),
				_: 1
			}, 8, ["data", "columns"])) : (w(), _(u, {
				key: 1,
				class: "h-32"
			}))]);
		};
	}
}), L = { class: "mb-4 text-sm text-muted" }, R = /* @__PURE__ */ C({
	__name: "types",
	setup(e) {
		let { t } = j();
		return m({ title: () => t("polycart.types.title") }), (e, n) => {
			let r = a, i = u, s = o;
			return w(), _(s, null, {
				header: A(() => [S(r, { title: k(t)("polycart.types.title") }, null, 8, ["title"])]),
				body: A(() => [b("p", L, O(k(t)("polycart.types.description")), 1), S(i, { "screen-key": "polycart.types" }, {
					default: A(() => [S(I)]),
					_: 1
				})]),
				_: 1
			});
		};
	}
});
export { R as default };
