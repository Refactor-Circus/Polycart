import { i as e, r as t } from "./chunks/polycart-pC5iCRim.js";
import { UBadge as n, UButton as r, UEmpty as i, ULink as a, USkeleton as o } from "@refactor-circus/atrium/ui";
import { AtriumErrorCard as s, AtriumNoAccess as c, defineAtriumModule as l, integrationData as u, mergeAtriumMessages as d, useAtriumIntegration as f, useIntegrationQuery as p } from "@refactor-circus/atrium";
import { Fragment as m, computed as h, createBlock as g, createElementBlock as _, createElementVNode as v, createTextVNode as y, createVNode as b, defineComponent as x, openBlock as S, renderList as C, toDisplayString as w, unref as T, withCtx as E } from "vue";
import { useI18n as D } from "vue-i18n";
// src/components/usePolycartStats.ts
function O() {
	return p("polycart", "polycart.stats.show", async (e) => u(await e.GET("/stats")).data);
}
// src/components/PolycartCartsByType.vue?vue&type=script&setup=true&lang.ts
var k = {
	class: "flex flex-col gap-3",
	"data-testid": "polycart-carts-by-type"
}, A = { class: "text-sm font-medium" }, j = {
	key: 3,
	class: "flex flex-wrap gap-2"
}, M = { class: "text-sm font-semibold tabular-nums" }, N = /* @__PURE__ */ x({
	__name: "PolycartCartsByType",
	props: {
		data: {},
		settings: {}
	},
	setup(e) {
		let { t } = D(), { data: i, status: a, forbidden: l } = O();
		function u(e) {
			return {
				path: "/polycart/carts",
				query: {
					type: e,
					unexpired: "1"
				}
			};
		}
		return (e, d) => {
			let f = c, p = s, h = o, y = n, x = r;
			return S(), _("div", k, [v("p", A, w(T(t)("polycart.widgets.carts_by_type")), 1), T(l) ? (S(), g(f, {
				key: 0,
				compact: ""
			})) : T(a) === "error" ? (S(), g(p, {
				key: 1,
				message: T(t)("atrium.widget.failed")
			}, null, 8, ["message"])) : T(i) === null ? (S(), g(h, {
				key: 2,
				class: "h-10"
			})) : (S(), _("div", j, [(S(!0), _(m, null, C(T(i).by_type, (e) => (S(), g(x, {
				key: e.type,
				to: u(e.type),
				color: "neutral",
				variant: "outline",
				"data-testid": "polycart-type-count"
			}, {
				default: E(() => [b(y, {
					color: "primary",
					variant: "subtle",
					label: e.type
				}, null, 8, ["label"]), v("span", M, w(e.count), 1)]),
				_: 2
			}, 1032, ["to"]))), 128))]))]);
		};
	}
}), P = {
	class: "flex flex-col gap-3",
	"data-testid": "polycart-recent-carts"
}, F = { class: "text-sm font-medium" }, I = {
	key: 4,
	class: "flex flex-col gap-2"
}, L = { class: "flex min-w-0 items-center gap-2" }, R = { class: "shrink-0 text-muted" }, z = /* @__PURE__ */ x({
	__name: "PolycartRecentCarts",
	props: {
		data: {},
		settings: {}
	},
	setup(r) {
		let { t: l, locale: u } = D(), { data: d, status: f, forbidden: p } = O(), x = h(() => new Intl.DateTimeFormat(u.value, {
			dateStyle: "medium",
			timeStyle: "short"
		}));
		function k(e) {
			return e === null ? "" : x.value.format(new Date(e));
		}
		return (r, u) => {
			let h = c, x = s, D = o, O = i, A = n, j = a;
			return S(), _("div", P, [v("p", F, w(T(l)("polycart.widgets.recent_carts")), 1), T(p) ? (S(), g(h, {
				key: 0,
				compact: ""
			})) : T(f) === "error" ? (S(), g(x, {
				key: 1,
				message: T(l)("atrium.widget.failed")
			}, null, 8, ["message"])) : T(d) === null ? (S(), g(D, {
				key: 2,
				class: "h-10"
			})) : T(d).recent.length === 0 ? (S(), g(O, {
				key: 3,
				icon: "i-lucide-shopping-cart",
				title: T(l)("polycart.widgets.no_carts"),
				size: "xs",
				variant: "naked",
				"data-testid": "polycart-no-recent-carts"
			}, null, 8, ["title"])) : (S(), _("ul", I, [(S(!0), _(m, null, C(T(d).recent, (n) => (S(), _("li", {
				key: n.id,
				class: "flex items-center justify-between gap-2 text-sm",
				"data-testid": "polycart-recent-cart"
			}, [v("span", L, [b(A, {
				color: "primary",
				variant: "subtle",
				label: n.type
			}, null, 8, ["label"]), b(j, {
				to: T(t)(n.id),
				class: "truncate underline-offset-2 hover:underline"
			}, {
				default: E(() => [y(w(T(e)(n)), 1)]),
				_: 2
			}, 1032, ["to"])]), v("span", R, w(T(l)("polycart.widgets.line_count", n.lines_count ?? 0)) + " · " + w(k(n.updated_at)), 1)]))), 128))]))]);
		};
	}
});
// src/components/search.ts
async function B(n, { limit: r }) {
	let { client: i } = f("polycart");
	return i ? u(await i.GET("/search", { params: { query: {
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
var V = { polycart: {
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
} }, H = [
	{
		path: "/polycart/carts/:cart",
		name: "polycart-carts-cart",
		component: () => import("./chunks/_cart_-BYfLo4mJ.js")
	},
	{
		path: "/polycart/carts",
		name: "polycart-carts",
		component: () => import("./chunks/carts-bRIAoEov.js")
	},
	{
		path: "/polycart/types",
		name: "polycart-types",
		component: () => import("./chunks/types-BgCgeIlO.js")
	}
], U = {
	"polycart.carts": "polycart-carts",
	"polycart.cart": "polycart-carts-cart",
	"polycart.types": "polycart-types"
}, W = l({
	key: "polycart",
	routes: H,
	screens: U,
	widgets: {
		"polycart.carts-by-type": N,
		"polycart.recent-carts": z
	},
	searchSources: { polycart: B },
	messages: { en: d(V) }
});
export { W as default, U as screens };
