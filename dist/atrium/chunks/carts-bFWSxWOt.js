import { t as e } from "./PolycartCartStatus-BkBDCIMd.js";
import { a as t, f as n, i as r, o as i, r as a, s as o, u as ee } from "./polycart-pC5iCRim.js";
import { UAlert as te, UBadge as ne, UButton as re, UDashboardNavbar as s, UDashboardPanel as c, UEmpty as ie, UForm as ae, UFormField as oe, UInput as se, ULink as ce, USelect as le, USkeleton as ue, UTable as de } from "@refactor-circus/atrium/ui";
import { AtriumIntegrationError as l, AtriumIntegrationScreen as u, AtriumNoAccess as fe, AtriumState as pe, atriumStatus as d, useHead as f } from "@refactor-circus/atrium";
import { Fragment as p, computed as m, createBlock as h, createCommentVNode as g, createElementBlock as _, createElementVNode as v, createTextVNode as y, createVNode as b, defineComponent as x, isRef as me, normalizeClass as he, openBlock as S, reactive as C, ref as w, renderList as ge, shallowRef as T, toDisplayString as E, unref as D, withCtx as O } from "vue";
import { useI18n as k } from "vue-i18n";
import { useRoute as A, useRouter as _e } from "vue-router";
// src/components/PolycartCarts.vue?vue&type=script&setup=true&lang.ts
var ve = {
	class: "flex flex-col gap-4",
	"data-testid": "polycart-carts"
}, ye = { class: "flex gap-2" }, be = {
	key: 1,
	class: "text-muted"
}, xe = {
	key: 1,
	class: "text-muted"
}, j = {
	key: 4,
	class: "flex items-center justify-between gap-3 text-sm"
}, M = "*", N = /* @__PURE__ */ x({
	__name: "PolycartCarts",
	setup(s) {
		let c = [
			"type",
			"status",
			"source",
			"search"
		], { t: u, locale: f } = k(), x = A(), N = _e(), P = n();
		function F(e) {
			let t = x.query[e];
			return typeof t == "string" ? t : "";
		}
		let I = C({
			type: F("type"),
			status: F("status"),
			source: F("source"),
			search: F("search")
		}), L = w({ ...I }), R = w(["1", "true"].includes(F("unexpired"))), z = w(null), B = T([]), V = w(null), H = w(null), U = T([]), W = w(!1), G = w(!1), K = w(!1), q = w([]), Se = m(() => new Intl.DateTimeFormat(f.value, {
			dateStyle: "medium",
			timeStyle: "short"
		})), Ce = m(() => [{
			value: M,
			label: u("polycart.carts.all_types")
		}, ...U.value.map((e) => ({
			value: e,
			label: e
		}))]), J = m({
			get: () => I.type === "" ? M : I.type,
			set: (e) => {
				I.type = e === M ? "" : e;
			}
		}), Y = m(() => [
			{
				id: "cart",
				header: u("polycart.carts.cart")
			},
			{
				id: "type",
				header: u("polycart.carts.type")
			},
			{
				id: "status",
				header: u("polycart.carts.status")
			},
			{
				id: "owner",
				header: u("polycart.carts.owner")
			},
			{
				id: "source",
				header: u("polycart.carts.source")
			},
			{
				id: "lines",
				header: u("polycart.carts.lines"),
				meta: { class: {
					th: "text-right",
					td: "text-right tabular-nums"
				} }
			},
			{
				id: "updated",
				header: u("polycart.carts.updated"),
				meta: { class: { td: "text-muted" } }
			},
			{
				id: "expires",
				header: u("polycart.carts.expires")
			}
		]);
		function we() {
			let e = {};
			for (let t of c) L.value[t] !== "" && (e[t] = L.value[t]);
			return R.value && (e.unexpired = "1"), z.value !== null && (e.cursor = z.value), e;
		}
		async function X() {
			if (!P) W.value = !0;
			else try {
				let e = t(await P.GET("/carts", { params: { query: o(we()) } }));
				B.value = e.data, V.value = e.meta.next_cursor, H.value = e.meta.prev_cursor, G.value = !1, q.value = [];
			} catch (e) {
				let t = d(e), n = e instanceof l ? e.body : null;
				K.value = t === 403, q.value = t === 422 ? Object.values(n?.errors ?? {}).flat() : [], G.value = t !== 403 && t !== 422, B.value = [];
			} finally {
				W.value = !0;
			}
		}
		async function Te() {
			if (P) try {
				U.value = t(await P.GET("/types")).data.map((e) => e.key);
			} catch {}
		}
		async function Z() {
			L.value = { ...I }, R.value = !1, z.value = null;
			let e = Object.fromEntries(Object.entries(x.query).filter(([e]) => !c.includes(e) && e !== "unexpired")), t = Object.fromEntries(c.filter((e) => L.value[e] !== "").map((e) => [e, L.value[e]]));
			await N.replace({ query: {
				...e,
				...t
			} }), await X();
		}
		async function Ee() {
			Object.assign(I, {
				type: "",
				status: "",
				source: "",
				search: ""
			}), await Z();
		}
		async function Q(e) {
			z.value = e, await X();
		}
		function $(e) {
			return e === null ? "" : Se.value.format(new Date(e));
		}
		return Promise.all([Te(), X()]), (t, n) => {
			let o = fe, s = le, c = oe, l = se, d = re, f = ae, m = te, x = ue, C = pe, w = ie, T = ce, k = ne, A = de;
			return S(), _("div", ve, [D(K) ? (S(), h(o, { key: 0 })) : (S(), _(p, { key: 1 }, [
				b(f, {
					state: D(I),
					class: "grid items-end gap-3 sm:grid-cols-2 lg:grid-cols-5",
					"data-testid": "polycart-filters",
					onSubmit: Z
				}, {
					default: O(() => [
						b(c, {
							label: D(u)("polycart.carts.type"),
							name: "type"
						}, {
							default: O(() => [b(s, {
								modelValue: D(J),
								"onUpdate:modelValue": n[0] ||= (e) => me(J) ? J.value = e : null,
								items: D(Ce),
								class: "w-full",
								"data-testid": "polycart-filter-type"
							}, null, 8, ["modelValue", "items"])]),
							_: 1
						}, 8, ["label"]),
						b(c, {
							label: D(u)("polycart.carts.status"),
							name: "status"
						}, {
							default: O(() => [b(l, {
								modelValue: D(I).status,
								"onUpdate:modelValue": n[1] ||= (e) => D(I).status = e,
								class: "w-full",
								"data-testid": "polycart-filter-status"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						b(c, {
							label: D(u)("polycart.carts.source"),
							name: "source"
						}, {
							default: O(() => [b(l, {
								modelValue: D(I).source,
								"onUpdate:modelValue": n[2] ||= (e) => D(I).source = e,
								class: "w-full",
								"data-testid": "polycart-filter-source"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						b(c, {
							label: D(u)("polycart.carts.search"),
							name: "search"
						}, {
							default: O(() => [b(l, {
								modelValue: D(I).search,
								"onUpdate:modelValue": n[3] ||= (e) => D(I).search = e,
								class: "w-full",
								"data-testid": "polycart-filter-search"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						v("div", ye, [b(d, {
							type: "submit",
							variant: "outline",
							icon: "i-lucide-funnel",
							label: D(u)("polycart.carts.filter"),
							"data-testid": "polycart-filter"
						}, null, 8, ["label"]), b(d, {
							color: "neutral",
							variant: "ghost",
							icon: "i-lucide-x",
							label: D(u)("polycart.carts.reset"),
							"data-testid": "polycart-reset",
							onClick: Ee
						}, null, 8, ["label"])])
					]),
					_: 1
				}, 8, ["state"]),
				D(q).length > 0 ? (S(), h(m, {
					key: 0,
					color: "error",
					variant: "subtle",
					icon: "i-lucide-circle-alert",
					"data-testid": "polycart-filter-errors"
				}, {
					description: O(() => [v("ul", null, [(S(!0), _(p, null, ge(D(q), (e) => (S(), _("li", { key: e }, E(e), 1))), 128))])]),
					_: 1
				})) : g("", !0),
				D(W) ? D(G) ? (S(), h(C, {
					key: 2,
					kind: "error",
					title: D(u)("polycart.carts.load_failed"),
					"data-testid": "polycart-load-error"
				}, {
					default: O(() => [b(d, {
						size: "sm",
						variant: "outline",
						icon: "i-lucide-refresh-cw",
						label: D(u)("polycart.retry"),
						"data-testid": "polycart-retry",
						onClick: n[4] ||= (e) => X()
					}, null, 8, ["label"])]),
					_: 1
				}, 8, ["title"])) : (S(), h(A, {
					key: 3,
					data: D(B),
					columns: D(Y),
					class: "rounded-md border border-default",
					"data-testid": "polycart-carts-table"
				}, {
					empty: O(() => [b(w, {
						icon: "i-lucide-shopping-cart",
						title: D(u)("polycart.carts.no_carts"),
						variant: "naked",
						"data-testid": "polycart-no-carts"
					}, null, 8, ["title"])]),
					"cart-cell": O(({ row: e }) => [b(T, {
						to: D(a)(e.original.id),
						class: "font-medium underline-offset-2 hover:underline",
						"data-testid": "polycart-cart-link"
					}, {
						default: O(() => [y(E(D(r)(e.original)), 1)]),
						_: 2
					}, 1032, ["to"])]),
					"type-cell": O(({ row: e }) => [b(k, {
						color: "primary",
						variant: "subtle",
						label: e.original.type
					}, null, 8, ["label"])]),
					"status-cell": O(({ row: t }) => [t.original.status ? (S(), h(e, {
						key: 0,
						status: t.original.status,
						expired: D(i)(t.original)
					}, null, 8, ["status", "expired"])) : (S(), _("span", be, "—"))]),
					"owner-cell": O(({ row: e }) => [y(E(D(ee)(e.original) ?? D(u)("polycart.guest")), 1)]),
					"source-cell": O(({ row: e }) => [b(k, {
						color: "neutral",
						variant: "subtle",
						label: e.original.source ?? "—"
					}, null, 8, ["label"])]),
					"lines-cell": O(({ row: e }) => [y(E(e.original.lines_count ?? ""), 1)]),
					"updated-cell": O(({ row: e }) => [y(E($(e.original.updated_at)), 1)]),
					"expires-cell": O(({ row: e }) => [e.original.expires_at ? (S(), _("span", {
						key: 0,
						class: he(D(i)(e.original) ? "text-warning" : "text-muted")
					}, E($(e.original.expires_at)), 3)) : (S(), _("span", xe, E(D(u)("polycart.never")), 1))]),
					_: 1
				}, 8, ["data", "columns"])) : (S(), h(x, {
					key: 1,
					class: "h-32"
				})),
				D(W) && !D(G) && (D(V) !== null || D(H) !== null) ? (S(), _("div", j, [b(d, {
					size: "sm",
					color: "neutral",
					variant: "outline",
					icon: "i-lucide-chevron-left",
					label: D(u)("polycart.carts.previous"),
					disabled: D(H) === null,
					"data-testid": "polycart-prev",
					onClick: n[5] ||= (e) => Q(D(H))
				}, null, 8, ["label", "disabled"]), b(d, {
					size: "sm",
					color: "neutral",
					variant: "outline",
					"trailing-icon": "i-lucide-chevron-right",
					label: D(u)("polycart.carts.next"),
					disabled: D(V) === null,
					"data-testid": "polycart-next",
					onClick: n[6] ||= (e) => Q(D(V))
				}, null, 8, ["label", "disabled"])])) : g("", !0)
			], 64))]);
		};
	}
}), P = /* @__PURE__ */ x({
	__name: "index",
	setup(e) {
		let { t } = k();
		return f({ title: () => t("polycart.carts.title") }), (e, n) => {
			let r = s, i = u, a = c;
			return S(), h(a, null, {
				header: O(() => [b(r, { title: D(t)("polycart.carts.title") }, null, 8, ["title"])]),
				body: O(() => [b(i, { "screen-key": "polycart.carts" }, {
					default: O(() => [b(N)]),
					_: 1
				})]),
				_: 1
			});
		};
	}
});
export { P as default };
