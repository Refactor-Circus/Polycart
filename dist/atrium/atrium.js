import { i as e, r as t } from "./chunks/polycart-pC5iCRim.js";
import { UBadge as n, USkeleton as r } from "@refactor-circus/atrium/ui";
import { AtriumErrorCard as i, AtriumNoAccess as a, defineAtriumModule as o, integrationData as s, mergeAtriumMessages as c, useAtriumIntegration as l, useIntegrationQuery as u } from "@refactor-circus/atrium";
import { Fragment as d, computed as f, createBlock as p, createElementBlock as m, createElementVNode as h, createTextVNode as g, createVNode as _, defineComponent as v, openBlock as y, renderList as b, resolveComponent as x, toDisplayString as S, unref as C, withCtx as w } from "vue";
import { useI18n as T } from "vue-i18n";
// src/components/usePolycartStats.ts
function E() {
	return u("polycart", "polycart.stats.show", async (e) => s(await e.GET("/stats")).data);
}
// src/components/PolycartCartsByType.vue?vue&type=script&setup=true&lang.ts
var D = {
	class: "flex flex-col gap-3",
	"data-testid": "polycart-carts-by-type"
}, O = { class: "text-sm font-medium" }, k = {
	key: 3,
	class: "flex flex-wrap gap-2"
}, A = { class: "text-sm font-semibold tabular-nums" }, j = /* @__PURE__ */ v({
	__name: "PolycartCartsByType",
	props: {
		data: {},
		settings: {}
	},
	setup(e) {
		let { t } = T(), { data: o, status: s, forbidden: c } = E();
		function l(e) {
			return {
				path: "/polycart/carts",
				query: {
					type: e,
					unexpired: "1"
				}
			};
		}
		return (e, u) => {
			let f = a, g = i, v = r, T = n, E = x("RouterLink");
			return y(), m("div", D, [h("p", O, S(C(t)("polycart.widgets.carts_by_type")), 1), C(c) ? (y(), p(f, {
				key: 0,
				compact: ""
			})) : C(s) === "error" ? (y(), p(g, {
				key: 1,
				message: C(t)("atrium.widget.failed")
			}, null, 8, ["message"])) : C(o) === null ? (y(), p(v, {
				key: 2,
				class: "h-10"
			})) : (y(), m("div", k, [(y(!0), m(d, null, b(C(o).by_type, (e) => (y(), p(E, {
				key: e.type,
				to: l(e.type),
				class: "flex items-center gap-2 rounded-md border border-default px-3 py-2 transition hover:bg-elevated",
				"data-testid": "polycart-type-count"
			}, {
				default: w(() => [_(T, {
					color: "primary",
					variant: "subtle",
					label: e.type
				}, null, 8, ["label"]), h("span", A, S(e.count), 1)]),
				_: 2
			}, 1032, ["to"]))), 128))]))]);
		};
	}
}), M = {
	class: "flex flex-col gap-3",
	"data-testid": "polycart-recent-carts"
}, N = { class: "text-sm font-medium" }, P = {
	key: 3,
	class: "text-sm text-muted",
	"data-testid": "polycart-no-recent-carts"
}, F = {
	key: 4,
	class: "flex flex-col gap-2"
}, I = { class: "flex min-w-0 items-center gap-2" }, L = { class: "shrink-0 text-muted" }, R = /* @__PURE__ */ v({
	__name: "PolycartRecentCarts",
	props: {
		data: {},
		settings: {}
	},
	setup(o) {
		let { t: s, locale: c } = T(), { data: l, status: u, forbidden: v } = E(), D = f(() => new Intl.DateTimeFormat(c.value, {
			dateStyle: "medium",
			timeStyle: "short"
		}));
		function O(e) {
			return e === null ? "" : D.value.format(new Date(e));
		}
		return (o, c) => {
			let f = a, T = i, E = r, D = n, k = x("RouterLink");
			return y(), m("div", M, [h("p", N, S(C(s)("polycart.widgets.recent_carts")), 1), C(v) ? (y(), p(f, {
				key: 0,
				compact: ""
			})) : C(u) === "error" ? (y(), p(T, {
				key: 1,
				message: C(s)("atrium.widget.failed")
			}, null, 8, ["message"])) : C(l) === null ? (y(), p(E, {
				key: 2,
				class: "h-10"
			})) : C(l).recent.length === 0 ? (y(), m("p", P, S(C(s)("polycart.widgets.no_carts")), 1)) : (y(), m("ul", F, [(y(!0), m(d, null, b(C(l).recent, (n) => (y(), m("li", {
				key: n.id,
				class: "flex items-center justify-between gap-2 text-sm",
				"data-testid": "polycart-recent-cart"
			}, [h("span", I, [_(D, {
				color: "primary",
				variant: "subtle",
				label: n.type
			}, null, 8, ["label"]), _(k, {
				to: C(t)(n.id),
				class: "truncate underline-offset-2 hover:underline"
			}, {
				default: w(() => [g(S(C(e)(n)), 1)]),
				_: 2
			}, 1032, ["to"])]), h("span", L, S(C(s)("polycart.widgets.line_count", n.lines_count ?? 0)) + " · " + S(O(n.updated_at)), 1)]))), 128))]))]);
		};
	}
});
// src/components/search.ts
async function z(n, { limit: r }) {
	let { client: i } = l("polycart");
	return i ? s(await i.GET("/search", { params: { query: {
		q: n,
		per_page: r
	} } })).data.slice(0, r).map((n) => ({
		title: e(n),
		subtitle: n.status === null ? n.type : `${n.type} · ${n.status}`,
		icon: "i-lucide-shopping-cart",
		group: null,
		link: {
			path: t(n.id),
			external: !1
		}
	})) : [];
}
var B = { polycart: {
	never: "Never",
	guest: "Guest",
	status_expired: "{status} (expired)",
	retry: "Retry",
	visibility: {
		private: "Members only",
		scope: "Everyone in its scope",
		boundary: "Everyone in its boundary"
	},
	carts: {
		title: "Carts",
		type: "Type",
		all_types: "All types",
		status: "Status",
		source: "Source",
		search: "Search",
		filter: "Filter",
		reset: "Reset",
		cart: "Cart",
		owner: "Owner",
		lines: "Lines",
		updated: "Updated",
		expires: "Expires",
		no_carts: "No carts match.",
		previous: "Previous",
		next: "Next",
		load_failed: "The carts could not be loaded."
	},
	cart: {
		heading: "Cart",
		all_carts: "All carts",
		not_found: "This cart does not exist, or is no longer available.",
		load_failed: "This cart could not be loaded.",
		forbidden: "You may not make this change.",
		clear: "Clear",
		clear_confirm: "Remove every line from this cart?",
		delete: "Delete",
		delete_confirm: "Delete this cart? This cannot be undone.",
		subtotal: "Subtotal",
		quantity: "Quantity",
		lines: "Lines",
		cart_id: "Cart ID",
		owner: "Owner",
		source: "Source",
		expires: "Expires",
		updated_at: "Updated",
		scope: "Scope",
		unscoped: "Not in a scope",
		parent: "Parent",
		unknown_type: "The type {type} is no longer registered, so this cart cannot be changed.",
		lifecycle: "Lifecycle",
		move_to: "Move to",
		move: "Move",
		convert_to: "Convert to",
		keep_original: "Keep the original",
		convert: "Convert",
		no_lines: "This cart is empty.",
		item: "Item",
		options: "Options",
		unit_price: "Unit price",
		total: "Total",
		custom: "Custom",
		update: "Update",
		remove: "Remove",
		unpriced_lines: "Some lines have no price and are left out of the subtotal.",
		members: "Members",
		visible_to: "Visible to",
		save: "Save",
		no_members: "Nobody has been added to this cart.",
		member_type: "Member",
		member_type_hint: "The member's morph alias, such as user or team",
		member_id: "ID",
		role: "Role",
		share: "Share",
		children: "Nested carts",
		details: "Details",
		label: "Label",
		meta: "Meta (JSON)",
		meta_invalid: "Meta must be a JSON object.",
		updated: "Cart updated.",
		deleted: "Cart deleted.",
		cleared: "Cart cleared.",
		status_changed: "Status changed.",
		converted: "Cart converted.",
		visibility_changed: "Visibility changed.",
		line_updated: "Line updated.",
		line_removed: "Line removed.",
		shared: "Cart shared.",
		unshared: "Member removed."
	},
	types: {
		title: "Cart types",
		description: "Every registered type and the rules it enforces.",
		type: "Type",
		statuses: "Statuses",
		converts_to: "Converts to",
		parents: "Parents",
		rules: "Rules",
		lifetime: "Lifetime",
		any: "Any",
		root_only: "Root only",
		requires_price: "Priced",
		requires_parent: "Nested",
		separate_lines: "Separate lines",
		load_failed: "The cart types could not be loaded."
	},
	widgets: {
		carts_by_type: "Carts by type",
		recent_carts: "Recent carts",
		no_carts: "No carts yet.",
		line_count: "No lines | 1 line | {count} lines"
	}
} }, V = [
	{
		path: "/polycart/carts/:cart",
		name: "polycart-carts-cart",
		component: () => import("./chunks/_cart_-CNwpu-aj.js")
	},
	{
		path: "/polycart/carts",
		name: "polycart-carts",
		component: () => import("./chunks/carts-_IqDPmyh.js")
	},
	{
		path: "/polycart/types",
		name: "polycart-types",
		component: () => import("./chunks/types-5dxHecl0.js")
	}
], H = {
	"polycart.carts": "polycart-carts",
	"polycart.cart": "polycart-carts-cart",
	"polycart.types": "polycart-types"
}, U = o({
	key: "polycart",
	routes: V,
	screens: H,
	widgets: {
		"polycart.carts-by-type": j,
		"polycart.recent-carts": R
	},
	searchSources: { polycart: z },
	messages: { en: c(B) }
});
export { U as default, H as screens };
