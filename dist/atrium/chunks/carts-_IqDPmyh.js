import { t as e } from "./PolycartCartStatus-BkBDCIMd.js";
import { a as t, f as n, i as r, o as i, r as a, s as o, u as ee } from "./polycart-pC5iCRim.js";
import { UAlert as te, UBadge as ne, UButton as re, UDashboardNavbar as s, UDashboardPanel as c, UFormField as ie, UInput as ae, USkeleton as oe } from "@refactor-circus/atrium/ui";
import { AtriumIntegrationError as l, AtriumIntegrationScreen as u, AtriumNoAccess as se, atriumStatus as d, useHead as f } from "@refactor-circus/atrium";
import { Fragment as p, computed as m, createBlock as h, createCommentVNode as g, createElementBlock as _, createElementVNode as v, createTextVNode as ce, createVNode as y, defineComponent as b, normalizeClass as le, openBlock as x, reactive as ue, ref as S, renderList as C, resolveComponent as de, shallowRef as w, toDisplayString as T, unref as E, vModelSelect as fe, withCtx as D, withDirectives as pe, withModifiers as me } from "vue";
import { useI18n as O } from "vue-i18n";
import { useRoute as he, useRouter as ge } from "vue-router";
// src/components/PolycartCarts.vue?vue&type=script&setup=true&lang.ts
var _e = {
	class: "flex flex-col gap-4",
	"data-testid": "polycart-carts"
}, ve = { value: "" }, ye = ["value"], be = { class: "flex gap-2" }, k = {
	key: 2,
	class: "flex items-center justify-between gap-3 rounded-md border border-default p-4 text-sm",
	role: "alert",
	"data-testid": "polycart-load-error"
}, A = {
	key: 3,
	class: "atrium-empty",
	"data-testid": "polycart-no-carts"
}, j = {
	key: 4,
	class: "overflow-x-auto rounded-md border border-default"
}, M = {
	class: "w-full text-left text-sm",
	"data-testid": "polycart-carts-table"
}, N = { class: "bg-elevated text-muted" }, xe = { class: "px-3 py-2 font-medium" }, Se = { class: "px-3 py-2 font-medium" }, Ce = { class: "px-3 py-2 font-medium" }, we = { class: "px-3 py-2 font-medium" }, Te = { class: "px-3 py-2 font-medium" }, Ee = { class: "px-3 py-2 text-right font-medium" }, De = { class: "px-3 py-2 font-medium" }, Oe = { class: "px-3 py-2 font-medium" }, ke = { class: "divide-y divide-default" }, Ae = { class: "px-3 py-2" }, je = { class: "px-3 py-2" }, Me = { class: "px-3 py-2" }, Ne = {
	key: 1,
	class: "text-muted"
}, Pe = { class: "px-3 py-2" }, Fe = { class: "px-3 py-2" }, P = { class: "px-3 py-2 text-right tabular-nums" }, Ie = { class: "px-3 py-2 text-muted" }, Le = { class: "px-3 py-2" }, Re = {
	key: 1,
	class: "text-muted"
}, ze = {
	key: 5,
	class: "flex items-center justify-between gap-3 text-sm"
}, F = /* @__PURE__ */ b({
	__name: "PolycartCarts",
	setup(s) {
		let c = [
			"type",
			"status",
			"source",
			"search"
		], { t: u, locale: f } = O(), b = he(), F = ge(), I = n();
		function L(e) {
			let t = b.query[e];
			return typeof t == "string" ? t : "";
		}
		let R = ue({
			type: L("type"),
			status: L("status"),
			source: L("source"),
			search: L("search")
		}), z = S({ ...R }), B = S(["1", "true"].includes(L("unexpired"))), V = S(null), H = w([]), U = S(null), W = S(null), G = w([]), K = S(!1), q = S(!1), J = S(!1), Y = S([]), Be = m(() => new Intl.DateTimeFormat(f.value, {
			dateStyle: "medium",
			timeStyle: "short"
		}));
		function Ve() {
			let e = {};
			for (let t of c) z.value[t] !== "" && (e[t] = z.value[t]);
			return B.value && (e.unexpired = "1"), V.value !== null && (e.cursor = V.value), e;
		}
		async function X() {
			if (!I) K.value = !0;
			else try {
				let e = t(await I.GET("/carts", { params: { query: o(Ve()) } }));
				H.value = e.data, U.value = e.meta.next_cursor, W.value = e.meta.prev_cursor, q.value = !1, Y.value = [];
			} catch (e) {
				let t = d(e), n = e instanceof l ? e.body : null;
				J.value = t === 403, Y.value = t === 422 ? Object.values(n?.errors ?? {}).flat() : [], q.value = t !== 403 && t !== 422, H.value = [];
			} finally {
				K.value = !0;
			}
		}
		async function He() {
			if (I) try {
				G.value = t(await I.GET("/types")).data.map((e) => e.key);
			} catch {}
		}
		async function Z() {
			z.value = { ...R }, B.value = !1, V.value = null;
			let e = Object.fromEntries(Object.entries(b.query).filter(([e]) => !c.includes(e) && e !== "unexpired")), t = Object.fromEntries(c.filter((e) => z.value[e] !== "").map((e) => [e, z.value[e]]));
			await F.replace({ query: {
				...e,
				...t
			} }), await X();
		}
		async function Ue() {
			Object.assign(R, {
				type: "",
				status: "",
				source: "",
				search: ""
			}), await Z();
		}
		async function Q(e) {
			V.value = e, await X();
		}
		function $(e) {
			return e === null ? "" : Be.value.format(new Date(e));
		}
		return Promise.all([He(), X()]), (t, n) => {
			let o = se, s = ie, c = ae, l = re, d = te, f = oe, m = de("RouterLink"), b = ne;
			return x(), _("div", _e, [E(J) ? (x(), h(o, { key: 0 })) : (x(), _(p, { key: 1 }, [
				v("form", {
					class: "grid items-end gap-3 sm:grid-cols-2 lg:grid-cols-5",
					"data-testid": "polycart-filters",
					onSubmit: me(Z, ["prevent"])
				}, [
					y(s, { label: E(u)("polycart.carts.type") }, {
						default: D(() => [pe(v("select", {
							"onUpdate:modelValue": n[0] ||= (e) => E(R).type = e,
							class: "w-full rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
							"data-testid": "polycart-filter-type"
						}, [v("option", ve, T(E(u)("polycart.carts.all_types")), 1), (x(!0), _(p, null, C(E(G), (e) => (x(), _("option", {
							key: e,
							value: e
						}, T(e), 9, ye))), 128))], 512), [[fe, E(R).type]])]),
						_: 1
					}, 8, ["label"]),
					y(s, { label: E(u)("polycart.carts.status") }, {
						default: D(() => [y(c, {
							modelValue: E(R).status,
							"onUpdate:modelValue": n[1] ||= (e) => E(R).status = e,
							class: "w-full",
							"data-testid": "polycart-filter-status"
						}, null, 8, ["modelValue"])]),
						_: 1
					}, 8, ["label"]),
					y(s, { label: E(u)("polycart.carts.source") }, {
						default: D(() => [y(c, {
							modelValue: E(R).source,
							"onUpdate:modelValue": n[2] ||= (e) => E(R).source = e,
							class: "w-full",
							"data-testid": "polycart-filter-source"
						}, null, 8, ["modelValue"])]),
						_: 1
					}, 8, ["label"]),
					y(s, { label: E(u)("polycart.carts.search") }, {
						default: D(() => [y(c, {
							modelValue: E(R).search,
							"onUpdate:modelValue": n[3] ||= (e) => E(R).search = e,
							class: "w-full",
							"data-testid": "polycart-filter-search"
						}, null, 8, ["modelValue"])]),
						_: 1
					}, 8, ["label"]),
					v("div", be, [y(l, {
						type: "submit",
						variant: "outline",
						icon: "i-lucide-funnel",
						label: E(u)("polycart.carts.filter"),
						"data-testid": "polycart-filter"
					}, null, 8, ["label"]), y(l, {
						color: "neutral",
						variant: "ghost",
						icon: "i-lucide-x",
						label: E(u)("polycart.carts.reset"),
						"data-testid": "polycart-reset",
						onClick: Ue
					}, null, 8, ["label"])])
				], 32),
				E(Y).length > 0 ? (x(), h(d, {
					key: 0,
					color: "error",
					variant: "subtle",
					icon: "i-lucide-circle-alert",
					"data-testid": "polycart-filter-errors"
				}, {
					description: D(() => [v("ul", null, [(x(!0), _(p, null, C(E(Y), (e) => (x(), _("li", { key: e }, T(e), 1))), 128))])]),
					_: 1
				})) : g("", !0),
				E(K) ? E(q) ? (x(), _("div", k, [v("span", null, T(E(u)("polycart.carts.load_failed")), 1), y(l, {
					size: "sm",
					variant: "outline",
					icon: "i-lucide-refresh-cw",
					label: E(u)("polycart.retry"),
					"data-testid": "polycart-retry",
					onClick: n[4] ||= (e) => X()
				}, null, 8, ["label"])])) : E(H).length === 0 ? (x(), _("p", A, T(E(u)("polycart.carts.no_carts")), 1)) : (x(), _("div", j, [v("table", M, [v("thead", N, [v("tr", null, [
					v("th", xe, T(E(u)("polycart.carts.cart")), 1),
					v("th", Se, T(E(u)("polycart.carts.type")), 1),
					v("th", Ce, T(E(u)("polycart.carts.status")), 1),
					v("th", we, T(E(u)("polycart.carts.owner")), 1),
					v("th", Te, T(E(u)("polycart.carts.source")), 1),
					v("th", Ee, T(E(u)("polycart.carts.lines")), 1),
					v("th", De, T(E(u)("polycart.carts.updated")), 1),
					v("th", Oe, T(E(u)("polycart.carts.expires")), 1)
				])]), v("tbody", ke, [(x(!0), _(p, null, C(E(H), (t) => (x(), _("tr", {
					key: t.id,
					"data-testid": "polycart-cart-row"
				}, [
					v("td", Ae, [y(m, {
						to: E(a)(t.id),
						class: "font-medium underline-offset-2 hover:underline",
						"data-testid": "polycart-cart-link"
					}, {
						default: D(() => [ce(T(E(r)(t)), 1)]),
						_: 2
					}, 1032, ["to"])]),
					v("td", je, [y(b, {
						color: "primary",
						variant: "subtle",
						label: t.type
					}, null, 8, ["label"])]),
					v("td", Me, [t.status ? (x(), h(e, {
						key: 0,
						status: t.status,
						expired: E(i)(t)
					}, null, 8, ["status", "expired"])) : (x(), _("span", Ne, "—"))]),
					v("td", Pe, T(E(ee)(t) ?? E(u)("polycart.guest")), 1),
					v("td", Fe, [y(b, {
						color: "neutral",
						variant: "subtle",
						label: t.source ?? "—"
					}, null, 8, ["label"])]),
					v("td", P, T(t.lines_count ?? ""), 1),
					v("td", Ie, T($(t.updated_at)), 1),
					v("td", Le, [t.expires_at ? (x(), _("span", {
						key: 0,
						class: le(E(i)(t) ? "text-warning" : "text-muted")
					}, T($(t.expires_at)), 3)) : (x(), _("span", Re, T(E(u)("polycart.never")), 1))])
				]))), 128))])])])) : (x(), h(f, {
					key: 1,
					class: "h-32"
				})),
				E(K) && !E(q) && (E(U) !== null || E(W) !== null) ? (x(), _("div", ze, [y(l, {
					size: "sm",
					color: "neutral",
					variant: "outline",
					icon: "i-lucide-chevron-left",
					label: E(u)("polycart.carts.previous"),
					disabled: E(W) === null,
					"data-testid": "polycart-prev",
					onClick: n[5] ||= (e) => Q(E(W))
				}, null, 8, ["label", "disabled"]), y(l, {
					size: "sm",
					color: "neutral",
					variant: "outline",
					"trailing-icon": "i-lucide-chevron-right",
					label: E(u)("polycart.carts.next"),
					disabled: E(U) === null,
					"data-testid": "polycart-next",
					onClick: n[6] ||= (e) => Q(E(U))
				}, null, 8, ["label", "disabled"])])) : g("", !0)
			], 64))]);
		};
	}
}), I = /* @__PURE__ */ b({
	__name: "index",
	setup(e) {
		let { t } = O();
		return f({ title: () => t("polycart.carts.title") }), (e, n) => {
			let r = s, i = u, a = c;
			return x(), h(a, null, {
				header: D(() => [y(r, { title: E(t)("polycart.carts.title") }, null, 8, ["title"])]),
				body: D(() => [y(i, { "screen-key": "polycart.carts" }, {
					default: D(() => [y(F)]),
					_: 1
				})]),
				_: 1
			});
		};
	}
});
export { I as default };
