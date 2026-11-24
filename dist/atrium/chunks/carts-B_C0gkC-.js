import { t as e } from "./PolycartCartStatus-BkBDCIMd.js";
import { a as t, f as n, i as r, o as i, r as a, s as o, u as ee } from "./polycart-pC5iCRim.js";
import { UAlert as te, UBadge as ne, UButton as re, UDashboardNavbar as s, UDashboardPanel as c, UEmpty as ie, UForm as ae, UFormField as oe, UInput as se, ULink as l, USkeleton as ce, UTable as le } from "@refactor-circus/atrium/ui";
import { AtriumIntegrationError as u, AtriumIntegrationScreen as d, AtriumNoAccess as ue, AtriumSelect as f, AtriumState as de, atriumStatus as p, useHead as m } from "@refactor-circus/atrium";
import { Fragment as h, computed as g, createBlock as _, createCommentVNode as v, createElementBlock as y, createElementVNode as b, createTextVNode as x, createVNode as S, defineComponent as C, normalizeClass as w, openBlock as T, reactive as E, ref as D, renderList as fe, shallowRef as O, toDisplayString as k, unref as A, withCtx as j } from "vue";
import { useI18n as M } from "vue-i18n";
import { useRoute as N, useRouter as pe } from "vue-router";
// src/components/PolycartCarts.vue?vue&type=script&setup=true&lang.ts
var me = {
	class: "flex flex-col gap-4",
	"data-testid": "polycart-carts"
}, he = { class: "mt-field-label flex gap-2" }, ge = {
	key: 1,
	class: "text-muted"
}, _e = {
	key: 1,
	class: "text-muted"
}, P = {
	key: 4,
	class: "flex items-center justify-between gap-3 text-sm"
}, F = /* @__PURE__ */ C({
	__name: "PolycartCarts",
	setup(s) {
		let c = [
			"type",
			"status",
			"source",
			"search"
		], { t: d, locale: m } = M(), C = N(), F = pe(), I = n();
		function L(e) {
			let t = C.query[e];
			return typeof t == "string" ? t : "";
		}
		let R = E({
			type: L("type"),
			status: L("status"),
			source: L("source"),
			search: L("search")
		}), z = D({ ...R }), B = D(["1", "true"].includes(L("unexpired"))), V = D(null), H = O([]), U = D(null), W = D(null), G = O([]), K = D(!1), q = D(!1), J = D(!1), Y = D([]), ve = g(() => new Intl.DateTimeFormat(m.value, {
			dateStyle: "medium",
			timeStyle: "short"
		})), ye = g(() => G.value.map((e) => ({
			value: e,
			label: e
		}))), be = g(() => [
			{
				id: "cart",
				header: d("polycart.carts.cart")
			},
			{
				id: "type",
				header: d("polycart.carts.type")
			},
			{
				id: "status",
				header: d("polycart.carts.status")
			},
			{
				id: "owner",
				header: d("polycart.carts.owner")
			},
			{
				id: "source",
				header: d("polycart.carts.source")
			},
			{
				id: "lines",
				header: d("polycart.carts.lines"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			},
			{
				id: "updated",
				header: d("polycart.carts.updated"),
				meta: { class: { td: "text-muted" } }
			},
			{
				id: "expires",
				header: d("polycart.carts.expires")
			}
		]);
		function xe() {
			let e = {};
			for (let t of c) z.value[t] !== "" && (e[t] = z.value[t]);
			return B.value && (e.unexpired = "1"), V.value !== null && (e.cursor = V.value), e;
		}
		async function X() {
			if (!I) K.value = !0;
			else try {
				let e = t(await I.GET("/carts", { params: { query: o(xe()) } }));
				H.value = e.data, U.value = e.meta.next_cursor, W.value = e.meta.prev_cursor, q.value = !1, Y.value = [];
			} catch (e) {
				let t = p(e), n = e instanceof u ? e.body : null;
				J.value = t === 403, Y.value = t === 422 ? Object.values(n?.errors ?? {}).flat() : [], q.value = t !== 403 && t !== 422, H.value = [];
			} finally {
				K.value = !0;
			}
		}
		async function Se() {
			if (I) try {
				G.value = t(await I.GET("/types")).data.map((e) => e.key);
			} catch {}
		}
		async function Z() {
			z.value = { ...R }, B.value = !1, V.value = null;
			let e = Object.fromEntries(Object.entries(C.query).filter(([e]) => !c.includes(e) && e !== "unexpired")), t = Object.fromEntries(c.filter((e) => z.value[e] !== "").map((e) => [e, z.value[e]]));
			await F.replace({ query: {
				...e,
				...t
			} }), await X();
		}
		async function Ce() {
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
			return e === null ? "" : ve.value.format(new Date(e));
		}
		return Promise.all([Se(), X()]), (t, n) => {
			let o = ue, s = f, c = oe, u = se, p = re, m = ae, g = te, C = ce, E = de, D = ie, O = l, M = ne, N = le;
			return T(), y("div", me, [A(J) ? (T(), _(o, { key: 0 })) : (T(), y(h, { key: 1 }, [
				S(m, {
					state: A(R),
					class: "grid items-start gap-3 sm:grid-cols-2 lg:grid-cols-5",
					"data-testid": "polycart-filters",
					onSubmit: Z
				}, {
					default: j(() => [
						S(c, {
							label: A(d)("polycart.carts.type"),
							name: "type"
						}, {
							default: j(() => [S(s, {
								modelValue: A(R).type,
								"onUpdate:modelValue": n[0] ||= (e) => A(R).type = e,
								items: A(ye),
								blank: A(d)("polycart.carts.all_types"),
								class: "w-full",
								"data-testid": "polycart-filter-type"
							}, null, 8, [
								"modelValue",
								"items",
								"blank"
							])]),
							_: 1
						}, 8, ["label"]),
						S(c, {
							label: A(d)("polycart.carts.status"),
							name: "status"
						}, {
							default: j(() => [S(u, {
								modelValue: A(R).status,
								"onUpdate:modelValue": n[1] ||= (e) => A(R).status = e,
								class: "w-full",
								"data-testid": "polycart-filter-status"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						S(c, {
							label: A(d)("polycart.carts.source"),
							name: "source"
						}, {
							default: j(() => [S(u, {
								modelValue: A(R).source,
								"onUpdate:modelValue": n[2] ||= (e) => A(R).source = e,
								class: "w-full",
								"data-testid": "polycart-filter-source"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						S(c, {
							label: A(d)("polycart.carts.search"),
							name: "search"
						}, {
							default: j(() => [S(u, {
								modelValue: A(R).search,
								"onUpdate:modelValue": n[3] ||= (e) => A(R).search = e,
								class: "w-full",
								"data-testid": "polycart-filter-search"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						b("div", he, [S(p, {
							type: "submit",
							variant: "outline",
							icon: "i-lucide-funnel",
							label: A(d)("polycart.carts.filter"),
							"data-testid": "polycart-filter"
						}, null, 8, ["label"]), S(p, {
							color: "neutral",
							variant: "ghost",
							icon: "i-lucide-x",
							label: A(d)("polycart.carts.reset"),
							"data-testid": "polycart-reset",
							onClick: Ce
						}, null, 8, ["label"])])
					]),
					_: 1
				}, 8, ["state"]),
				A(Y).length > 0 ? (T(), _(g, {
					key: 0,
					color: "error",
					variant: "subtle",
					icon: "i-lucide-circle-alert",
					"data-testid": "polycart-filter-errors"
				}, {
					description: j(() => [b("ul", null, [(T(!0), y(h, null, fe(A(Y), (e) => (T(), y("li", { key: e }, k(e), 1))), 128))])]),
					_: 1
				})) : v("", !0),
				A(K) ? A(q) ? (T(), _(E, {
					key: 2,
					kind: "error",
					title: A(d)("polycart.carts.load_failed"),
					"data-testid": "polycart-load-error"
				}, {
					default: j(() => [S(p, {
						size: "sm",
						variant: "outline",
						icon: "i-lucide-refresh-cw",
						label: A(d)("polycart.retry"),
						"data-testid": "polycart-retry",
						onClick: n[4] ||= (e) => X()
					}, null, 8, ["label"])]),
					_: 1
				}, 8, ["title"])) : (T(), _(N, {
					key: 3,
					data: A(H),
					columns: A(be),
					class: "rounded-md border border-default",
					"data-testid": "polycart-carts-table"
				}, {
					empty: j(() => [S(D, {
						icon: "i-lucide-shopping-cart",
						title: A(d)("polycart.carts.no_carts"),
						variant: "naked",
						"data-testid": "polycart-no-carts"
					}, null, 8, ["title"])]),
					"cart-cell": j(({ row: e }) => [S(O, {
						to: A(a)(e.original.id),
						class: "font-medium underline-offset-2 hover:underline",
						"data-testid": "polycart-cart-link"
					}, {
						default: j(() => [x(k(A(r)(e.original)), 1)]),
						_: 2
					}, 1032, ["to"])]),
					"type-cell": j(({ row: e }) => [S(M, {
						color: "primary",
						variant: "subtle",
						label: e.original.type
					}, null, 8, ["label"])]),
					"status-cell": j(({ row: t }) => [t.original.status ? (T(), _(e, {
						key: 0,
						status: t.original.status,
						expired: A(i)(t.original)
					}, null, 8, ["status", "expired"])) : (T(), y("span", ge, "—"))]),
					"owner-cell": j(({ row: e }) => [x(k(A(ee)(e.original) ?? A(d)("polycart.guest")), 1)]),
					"source-cell": j(({ row: e }) => [S(M, {
						color: "neutral",
						variant: "subtle",
						label: e.original.source ?? "—"
					}, null, 8, ["label"])]),
					"lines-cell": j(({ row: e }) => [x(k(e.original.lines_count ?? ""), 1)]),
					"updated-cell": j(({ row: e }) => [x(k($(e.original.updated_at)), 1)]),
					"expires-cell": j(({ row: e }) => [e.original.expires_at ? (T(), y("span", {
						key: 0,
						class: w(A(i)(e.original) ? "text-warning" : "text-muted")
					}, k($(e.original.expires_at)), 3)) : (T(), y("span", _e, k(A(d)("polycart.never")), 1))]),
					_: 1
				}, 8, ["data", "columns"])) : (T(), _(C, {
					key: 1,
					class: "h-32"
				})),
				A(K) && !A(q) && (A(U) !== null || A(W) !== null) ? (T(), y("div", P, [S(p, {
					size: "sm",
					color: "neutral",
					variant: "outline",
					icon: "i-lucide-chevron-left",
					label: A(d)("polycart.carts.previous"),
					disabled: A(W) === null,
					"data-testid": "polycart-prev",
					onClick: n[5] ||= (e) => Q(A(W))
				}, null, 8, ["label", "disabled"]), S(p, {
					size: "sm",
					color: "neutral",
					variant: "outline",
					"trailing-icon": "i-lucide-chevron-right",
					label: A(d)("polycart.carts.next"),
					disabled: A(U) === null,
					"data-testid": "polycart-next",
					onClick: n[6] ||= (e) => Q(A(U))
				}, null, 8, ["label", "disabled"])])) : v("", !0)
			], 64))]);
		};
	}
}), I = /* @__PURE__ */ C({
	__name: "index",
	setup(e) {
		let { t } = M();
		return m({ title: () => t("polycart.carts.title") }), (e, n) => {
			let r = s, i = d, a = c;
			return T(), _(a, null, {
				header: j(() => [S(r, { title: A(t)("polycart.carts.title") }, null, 8, ["title"])]),
				body: j(() => [S(i, { "screen-key": "polycart.carts" }, {
					default: j(() => [S(F)]),
					_: 1
				})]),
				_: 1
			});
		};
	}
});
export { I as default };
