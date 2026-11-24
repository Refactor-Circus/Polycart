import { t as e } from "./PolycartCartStatus-BkBDCIMd.js";
import { a as t, c as n, f as r, i as ee, l as i, n as a, o as te, p as ne, r as re, s as o, t as ie, u as ae } from "./polycart-pC5iCRim.js";
import { UAlert as oe, UBadge as se, UButton as ce, UCard as le, UDashboardNavbar as s, UDashboardPanel as c, UFormField as ue, UInput as de, USkeleton as fe, UTextarea as pe, useToast as l } from "@refactor-circus/atrium/ui";
import { AtriumIntegrationScreen as u, AtriumNoAccess as me, atriumStatus as d, useHead as f } from "@refactor-circus/atrium";
import { Fragment as p, computed as m, createBlock as h, createCommentVNode as g, createElementBlock as _, createElementVNode as v, createTextVNode as he, createVNode as y, defineComponent as b, isRef as x, openBlock as S, reactive as C, ref as w, renderList as T, resolveComponent as ge, shallowRef as E, toDisplayString as D, unref as O, vModelCheckbox as _e, vModelSelect as ve, vModelText as ye, watch as be, withCtx as k, withDirectives as A, withModifiers as j } from "vue";
import { useI18n as xe } from "vue-i18n";
import { useRoute as M, useRouter as Se } from "vue-router";
// src/components/PolycartCart.vue?vue&type=script&setup=true&lang.ts
var Ce = {
	class: "flex flex-col gap-5",
	"data-testid": "polycart-cart"
}, we = {
	key: 2,
	class: "atrium-empty items-start text-start",
	"data-testid": "polycart-not-found"
}, Te = {
	key: 3,
	class: "flex items-center justify-between gap-3 rounded-md border border-default p-4 text-sm",
	role: "alert",
	"data-testid": "polycart-load-error"
}, Ee = { class: "flex flex-wrap items-center justify-between gap-3" }, De = { class: "flex min-w-0 items-center gap-2" }, Oe = {
	class: "truncate text-lg font-semibold",
	"data-testid": "polycart-cart-title"
}, ke = { class: "flex gap-2" }, Ae = { class: "grid gap-4 sm:grid-cols-3" }, je = {
	class: "rounded-md border border-default p-4",
	"data-testid": "polycart-subtotal"
}, Me = { class: "text-xs text-muted" }, Ne = { class: "text-lg font-semibold tabular-nums" }, Pe = {
	class: "rounded-md border border-default p-4",
	"data-testid": "polycart-quantity"
}, Fe = { class: "text-xs text-muted" }, Ie = { class: "text-lg font-semibold tabular-nums" }, Le = {
	class: "rounded-md border border-default p-4",
	"data-testid": "polycart-lines-count"
}, Re = { class: "text-xs text-muted" }, ze = { class: "text-lg font-semibold tabular-nums" }, Be = { class: "grid gap-x-6 gap-y-3 text-sm sm:grid-cols-[auto_1fr]" }, Ve = { class: "text-muted" }, He = { class: "font-mono" }, Ue = { class: "text-muted" }, We = { class: "text-muted" }, Ge = { class: "text-muted" }, Ke = { class: "text-muted" }, qe = { class: "text-muted" }, Je = {
	key: 1,
	class: "text-muted"
}, Ye = { class: "text-muted" }, Xe = { class: "font-medium" }, Ze = { class: "flex flex-wrap items-end gap-4" }, Qe = ["value"], $e = ["value"], et = { class: "flex items-center gap-2 pb-1.5 text-sm" }, tt = { class: "font-medium" }, nt = {
	key: 0,
	class: "text-sm text-muted",
	"data-testid": "polycart-no-lines"
}, rt = { class: "overflow-x-auto" }, it = { class: "w-full text-left text-sm" }, at = { class: "text-muted" }, ot = { class: "px-3 py-2 font-medium" }, st = { class: "px-3 py-2 text-right font-medium" }, ct = { class: "px-3 py-2 font-medium" }, lt = { class: "px-3 py-2 text-right font-medium" }, ut = { class: "px-3 py-2" }, dt = { class: "sr-only" }, ft = { class: "divide-y divide-default" }, pt = { class: "px-3 py-2" }, mt = {
	key: 0,
	class: "flex items-center gap-2"
}, ht = {
	key: 1,
	class: "font-mono"
}, gt = { class: "px-3 py-2 text-right tabular-nums" }, _t = { class: "px-3 py-2" }, vt = ["onSubmit"], yt = ["onUpdate:modelValue", "aria-label"], bt = {
	key: 0,
	class: "mt-1 text-xs text-error"
}, xt = { class: "px-3 py-2 text-right tabular-nums" }, St = { class: "px-3 py-2 text-right" }, Ct = {
	key: 0,
	class: "mt-3 text-sm text-warning",
	"data-testid": "polycart-unpriced"
}, wt = { class: "font-medium" }, Tt = { class: "flex flex-col gap-4" }, Et = ["value"], Dt = {
	key: 0,
	class: "text-sm text-muted"
}, Ot = {
	key: 1,
	class: "flex flex-col gap-2"
}, kt = { class: "flex items-center gap-2" }, At = { class: "font-mono" }, jt = { class: "font-medium" }, Mt = { class: "flex flex-col gap-2" }, Nt = { class: "font-medium" }, N = /* @__PURE__ */ b({
	__name: "PolycartCart",
	props: { cartId: {} },
	setup(s) {
		let c = s, { t: u, locale: f } = xe(), b = Se(), M = l(), N = r(), P = ne(), F = E(null), I = E(null), L = E([]), R = w(!1), z = w(!1), Pt = w(!1), Ft = w(!1), B = w(null), V = w(""), H = w(""), U = w(!0), W = w("private"), G = C({}), K = w(null), q = C({
			member_type: "",
			member_id: "",
			role: ""
		}), J = C({
			label: "",
			meta: "{}"
		}), It = w(null), Lt = m(() => new Intl.DateTimeFormat(f.value, {
			dateStyle: "medium",
			timeStyle: "short"
		})), Rt = m(() => F.value === null ? [] : [F.value]), zt = m(() => I.value?.find((e) => e.key === F.value?.type) ?? null), Bt = m(() => I.value !== null && F.value !== null && zt.value === null), Y = m(() => i(zt.value, F.value?.status ?? null)), X = m(() => zt.value?.converts_to ?? []);
		be(Y, (e) => {
			e.includes(V.value) || (V.value = e[0] ?? "");
		}, { immediate: !0 }), be(X, (e) => {
			e.includes(H.value) || (H.value = e[0] ?? "");
		}, { immediate: !0 });
		function Vt(e) {
			W.value = e.visibility ?? "private", J.label = e.label ?? "", J.meta = JSON.stringify(e.meta ?? {}, null, 2);
			for (let t of e.lines ?? []) G[t.id] = t.quantity;
		}
		async function Ht(e, n) {
			try {
				return t(await e.GET("/carts", { params: { query: { parent: n } } })).data;
			} catch {
				return [];
			}
		}
		async function Ut(e) {
			try {
				I.value = t(await e.GET("/types")).data;
			} catch {}
		}
		async function Z() {
			if (!N) R.value = !0;
			else try {
				let e = t(await N.GET("/carts/{cart}", { params: { path: { cart: c.cartId } } })).data;
				F.value = e, z.value = !1, Vt(e), L.value = (e.children_count ?? 0) > 0 ? await Ht(N, e.id) : [];
			} catch (e) {
				let t = d(e);
				Ft.value = t === 403, Pt.value = t === 404, z.value = t !== 403 && t !== 404;
			} finally {
				R.value = !0;
			}
		}
		async function Q(e, t) {
			return B.value = null, !N || !await P.run(() => e(N)) ? !1 : (B.value = t, await Z(), !0);
		}
		let $ = () => ({ path: { cart: c.cartId } });
		async function Wt() {
			It.value = null;
			let e;
			try {
				e = J.meta.trim() === "" ? null : JSON.parse(J.meta);
			} catch {
				e = void 0;
			}
			e === void 0 || e !== null && (typeof e != "object" || Array.isArray(e)) ? It.value = u("polycart.cart.meta_invalid") : await Q(async (n) => t(await n.PATCH("/carts/{cart}", {
				params: $(),
				body: {
					label: J.label === "" ? null : J.label,
					meta: o(e)
				}
			})), u("polycart.cart.updated"));
		}
		async function Gt() {
			N && window.confirm(u("polycart.cart.delete_confirm")) && (B.value = null, await P.run(async () => t(await N.DELETE("/carts/{cart}", { params: $() }))) && (M.add({
				title: u("polycart.cart.deleted"),
				color: "success"
			}), await b.push("/polycart/carts")));
		}
		async function Kt() {
			window.confirm(u("polycart.cart.clear_confirm")) && await Q(async (e) => t(await e.POST("/carts/{cart}/clear", { params: $() })), u("polycart.cart.cleared"));
		}
		async function qt() {
			await Q(async (e) => t(await e.POST("/carts/{cart}/status", {
				params: $(),
				body: { status: V.value }
			})), u("polycart.cart.status_changed"));
		}
		async function Jt() {
			if (!N) return;
			B.value = null;
			let e = { cart: null };
			await P.run(async () => {
				e.cart = t(await N.POST("/carts/{cart}/convert", {
					params: $(),
					body: {
						to: H.value,
						copy: U.value
					}
				})).data;
			}) && (e.cart !== null && e.cart.id !== c.cartId ? (M.add({
				title: u("polycart.cart.converted"),
				color: "success"
			}), await b.push(re(e.cart.id))) : (B.value = u("polycart.cart.converted"), await Z()));
		}
		async function Yt() {
			await Q(async (e) => t(await e.PUT("/carts/{cart}/visibility", {
				params: $(),
				body: { visibility: W.value }
			})), u("polycart.cart.visibility_changed"));
		}
		async function Xt(e) {
			K.value = e.id, await Q(async (n) => t(await n.PATCH("/carts/{cart}/lines", {
				params: $(),
				body: { lines: [{
					id: e.id,
					quantity: Number(G[e.id] ?? e.quantity)
				}] }
			})), u("polycart.cart.line_updated"));
		}
		async function Zt(e) {
			K.value = e.id, await Q(async (n) => t(await n.DELETE("/carts/{cart}/lines", { params: {
				...$(),
				query: { "lines[]": [e.id] }
			} })), u("polycart.cart.line_removed"));
		}
		async function Qt() {
			await Q(async (e) => t(await e.POST("/carts/{cart}/members", {
				params: $(),
				body: { ...q }
			})), u("polycart.cart.shared")) && Object.assign(q, {
				member_type: "",
				member_id: "",
				role: ""
			});
		}
		async function $t(e) {
			await Q(async (n) => t(await n.DELETE("/carts/{cart}/members/{member}", { params: { path: {
				cart: c.cartId,
				member: e
			} } })), u("polycart.cart.unshared"));
		}
		function en(e) {
			return e === null ? "" : Lt.value.format(new Date(e));
		}
		function tn(e) {
			if (e.purchasable_type !== null) return `${a(e.purchasable_type)} #${e.purchasable_id ?? ""}`;
			let t = e.meta?.description;
			return typeof t == "string" ? t : "";
		}
		return Promise.all([N ? Ut(N) : null, Z()]), (t, r) => {
			let i = me, ne = fe, o = ce, s = se, c = oe, l = ge("RouterLink"), d = le, f = ue, m = de, b = pe;
			return S(), _("div", Ce, [O(Ft) ? (S(), h(i, { key: 0 })) : O(R) ? O(Pt) ? (S(), _("div", we, [v("p", null, D(O(u)("polycart.cart.not_found")), 1), y(o, {
				size: "sm",
				variant: "outline",
				icon: "i-lucide-arrow-left",
				to: "/polycart/carts",
				label: O(u)("polycart.cart.all_carts")
			}, null, 8, ["label"])])) : O(z) || O(F) === null ? (S(), _("div", Te, [v("span", null, D(O(u)("polycart.cart.load_failed")), 1), y(o, {
				size: "sm",
				variant: "outline",
				icon: "i-lucide-refresh-cw",
				label: O(u)("polycart.retry"),
				"data-testid": "polycart-retry",
				onClick: r[0] ||= (e) => Z()
			}, null, 8, ["label"])])) : (S(!0), _(p, { key: 4 }, T(O(Rt), (t) => (S(), _(p, { key: t.id }, [
				v("div", Ee, [v("div", De, [
					v("h2", Oe, D(O(ee)(t)), 1),
					y(s, {
						color: "primary",
						variant: "subtle",
						label: t.type
					}, null, 8, ["label"]),
					t.status ? (S(), h(e, {
						key: 0,
						status: t.status,
						expired: O(te)(t)
					}, null, 8, ["status", "expired"])) : g("", !0)
				]), v("div", ke, [y(o, {
					size: "sm",
					color: "neutral",
					variant: "outline",
					icon: "i-lucide-archive-x",
					label: O(u)("polycart.cart.clear"),
					loading: O(P).busy.value,
					"data-testid": "polycart-clear",
					onClick: Kt
				}, null, 8, ["label", "loading"]), y(o, {
					size: "sm",
					color: "error",
					variant: "outline",
					icon: "i-lucide-trash",
					label: O(u)("polycart.cart.delete"),
					loading: O(P).busy.value,
					"data-testid": "polycart-delete",
					onClick: Gt
				}, null, 8, ["label", "loading"])])]),
				O(B) ? (S(), h(c, {
					key: 0,
					color: "success",
					variant: "subtle",
					icon: "i-lucide-check",
					title: O(B),
					"data-testid": "polycart-notice"
				}, null, 8, ["title"])) : g("", !0),
				O(P).forbidden.value ? (S(), h(i, {
					key: 1,
					compact: "",
					description: O(u)("polycart.cart.forbidden")
				}, null, 8, ["description"])) : g("", !0),
				O(P).message.value ? (S(), h(c, {
					key: 2,
					color: "error",
					variant: "subtle",
					icon: "i-lucide-circle-alert",
					title: O(P).message.value,
					"data-testid": "polycart-error"
				}, null, 8, ["title"])) : g("", !0),
				O(Bt) ? (S(), h(c, {
					key: 3,
					color: "warning",
					variant: "subtle",
					icon: "i-lucide-triangle-alert",
					title: O(u)("polycart.cart.unknown_type", { type: t.type }),
					"data-testid": "polycart-unknown-type"
				}, null, 8, ["title"])) : g("", !0),
				v("div", Ae, [
					v("div", je, [v("p", Me, D(O(u)("polycart.cart.subtotal")), 1), v("p", Ne, D(O(n)(t.subtotal)), 1)]),
					v("div", Pe, [v("p", Fe, D(O(u)("polycart.cart.quantity")), 1), v("p", Ie, D(t.quantity ?? 0), 1)]),
					v("div", Le, [v("p", Re, D(O(u)("polycart.cart.lines")), 1), v("p", ze, D(t.lines?.length ?? t.lines_count ?? 0), 1)])
				]),
				y(d, null, {
					default: k(() => [v("dl", Be, [
						v("dt", Ve, D(O(u)("polycart.cart.cart_id")), 1),
						v("dd", He, D(t.id), 1),
						v("dt", Ue, D(O(u)("polycart.cart.owner")), 1),
						v("dd", null, D(O(ae)(t) ?? O(u)("polycart.guest")), 1),
						v("dt", We, D(O(u)("polycart.cart.source")), 1),
						v("dd", null, D(t.source ?? "—"), 1),
						v("dt", Ge, D(O(u)("polycart.cart.expires")), 1),
						v("dd", null, D(t.expires_at ? en(t.expires_at) : O(u)("polycart.never")), 1),
						v("dt", Ke, D(O(u)("polycart.cart.updated_at")), 1),
						v("dd", null, D(en(t.updated_at)), 1),
						v("dt", qe, D(O(u)("polycart.cart.scope")), 1),
						v("dd", null, [t.scope_type ? (S(), h(s, {
							key: 0,
							color: "neutral",
							variant: "subtle",
							label: `${O(a)(t.scope_type)} #${t.scope_id ?? ""}`
						}, null, 8, ["label"])) : (S(), _("span", Je, D(O(u)("polycart.cart.unscoped")), 1))]),
						t.parent_id ? (S(), _(p, { key: 0 }, [v("dt", Ye, D(O(u)("polycart.cart.parent")), 1), v("dd", null, [y(l, {
							to: O(re)(t.parent_id),
							class: "font-mono underline-offset-2 hover:underline",
							"data-testid": "polycart-parent-link"
						}, {
							default: k(() => [he(D(t.parent_id), 1)]),
							_: 2
						}, 1032, ["to"])])], 64)) : g("", !0)
					])]),
					_: 2
				}, 1024),
				!O(Bt) && (O(Y).length > 0 || O(X).length > 0) ? (S(), h(d, {
					key: 4,
					"data-testid": "polycart-lifecycle"
				}, {
					header: k(() => [v("p", Xe, D(O(u)("polycart.cart.lifecycle")), 1)]),
					default: k(() => [v("div", Ze, [O(Y).length > 0 ? (S(), _("form", {
						key: 0,
						class: "flex items-end gap-2",
						"data-testid": "polycart-transition-form",
						onSubmit: j(qt, ["prevent"])
					}, [y(f, {
						label: O(u)("polycart.cart.move_to"),
						error: O(P).error("status")
					}, {
						default: k(() => [A(v("select", {
							"onUpdate:modelValue": r[1] ||= (e) => x(V) ? V.value = e : null,
							class: "w-48 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
							"data-testid": "polycart-transition-status"
						}, [(S(!0), _(p, null, T(O(Y), (e) => (S(), _("option", {
							key: e,
							value: e
						}, D(e), 9, Qe))), 128))], 512), [[ve, O(V)]])]),
						_: 1
					}, 8, ["label", "error"]), y(o, {
						type: "submit",
						icon: "i-lucide-arrow-right",
						label: O(u)("polycart.cart.move"),
						loading: O(P).busy.value,
						"data-testid": "polycart-transition"
					}, null, 8, ["label", "loading"])], 32)) : g("", !0), O(X).length > 0 ? (S(), _("form", {
						key: 1,
						class: "flex flex-wrap items-end gap-2",
						"data-testid": "polycart-convert-form",
						onSubmit: j(Jt, ["prevent"])
					}, [
						y(f, {
							label: O(u)("polycart.cart.convert_to"),
							error: O(P).error("to")
						}, {
							default: k(() => [A(v("select", {
								"onUpdate:modelValue": r[2] ||= (e) => x(H) ? H.value = e : null,
								class: "w-48 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
								"data-testid": "polycart-convert-to"
							}, [(S(!0), _(p, null, T(O(X), (e) => (S(), _("option", {
								key: e,
								value: e
							}, D(e), 9, $e))), 128))], 512), [[ve, O(H)]])]),
							_: 1
						}, 8, ["label", "error"]),
						v("label", et, [A(v("input", {
							"onUpdate:modelValue": r[3] ||= (e) => x(U) ? U.value = e : null,
							type: "checkbox",
							"data-testid": "polycart-convert-copy"
						}, null, 512), [[_e, O(U)]]), he(" " + D(O(u)("polycart.cart.keep_original")), 1)]),
						y(o, {
							type: "submit",
							icon: "i-lucide-arrow-right-left",
							label: O(u)("polycart.cart.convert"),
							loading: O(P).busy.value,
							"data-testid": "polycart-convert"
						}, null, 8, ["label", "loading"])
					], 32)) : g("", !0)])]),
					_: 1
				})) : g("", !0),
				y(d, null, {
					header: k(() => [v("p", tt, D(O(u)("polycart.cart.lines")), 1)]),
					default: k(() => [(t.lines ?? []).length === 0 ? (S(), _("p", nt, D(O(u)("polycart.cart.no_lines")), 1)) : (S(), _(p, { key: 1 }, [v("div", rt, [v("table", it, [v("thead", at, [v("tr", null, [
						v("th", ot, D(O(u)("polycart.cart.item")), 1),
						v("th", st, D(O(u)("polycart.cart.unit_price")), 1),
						v("th", ct, D(O(u)("polycart.cart.quantity")), 1),
						v("th", lt, D(O(u)("polycart.cart.total")), 1),
						v("th", ut, [v("span", dt, D(O(u)("polycart.cart.remove")), 1)])
					])]), v("tbody", ft, [(S(!0), _(p, null, T(t.lines, (e) => (S(), _("tr", {
						key: e.id,
						"data-testid": "polycart-line"
					}, [
						v("td", pt, [e.purchasable_type === null ? (S(), _("span", mt, [y(s, {
							color: "neutral",
							variant: "subtle",
							label: O(u)("polycart.cart.custom")
						}, null, 8, ["label"]), v("span", null, D(tn(e)), 1)])) : (S(), _("span", ht, D(tn(e)), 1))]),
						v("td", gt, D(O(n)(e.unit_price)), 1),
						v("td", _t, [v("form", {
							class: "flex items-center gap-2",
							"data-testid": "polycart-line-form",
							onSubmit: j((t) => Xt(e), ["prevent"])
						}, [A(v("input", {
							"onUpdate:modelValue": (t) => O(G)[e.id] = t,
							type: "number",
							min: "0",
							class: "w-20 rounded-md border border-default bg-default px-2 py-1 text-sm",
							"aria-label": O(u)("polycart.cart.quantity"),
							"data-testid": "polycart-line-quantity"
						}, null, 8, yt), [[
							ye,
							O(G)[e.id],
							void 0,
							{ number: !0 }
						]]), y(o, {
							type: "submit",
							size: "xs",
							variant: "ghost",
							icon: "i-lucide-check",
							label: O(u)("polycart.cart.update"),
							"data-testid": "polycart-line-update"
						}, null, 8, ["label"])], 40, vt), O(K) === e.id && O(P).error("lines") ? (S(), _("p", bt, D(O(P).error("lines")), 1)) : g("", !0)]),
						v("td", xt, D(O(n)(e.total)), 1),
						v("td", St, [y(o, {
							size: "xs",
							color: "neutral",
							variant: "ghost",
							icon: "i-lucide-trash",
							label: O(u)("polycart.cart.remove"),
							"data-testid": "polycart-line-remove",
							onClick: (t) => Zt(e)
						}, null, 8, ["label", "onClick"])])
					]))), 128))])])]), t.fully_priced === !1 ? (S(), _("p", Ct, D(O(u)("polycart.cart.unpriced_lines")), 1)) : g("", !0)], 64))]),
					_: 2
				}, 1024),
				y(d, null, {
					header: k(() => [v("p", wt, D(O(u)("polycart.cart.members")), 1)]),
					default: k(() => [v("div", Tt, [
						v("form", {
							class: "flex items-end gap-2",
							"data-testid": "polycart-visibility-form",
							onSubmit: j(Yt, ["prevent"])
						}, [y(f, {
							label: O(u)("polycart.cart.visible_to"),
							error: O(P).error("visibility")
						}, {
							default: k(() => [A(v("select", {
								"onUpdate:modelValue": r[4] ||= (e) => x(W) ? W.value = e : null,
								class: "w-72 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
								"data-testid": "polycart-visibility"
							}, [(S(!0), _(p, null, T(O(ie), (e) => (S(), _("option", {
								key: e,
								value: e
							}, D(O(u)(`polycart.visibility.${e}`)), 9, Et))), 128))], 512), [[ve, O(W)]])]),
							_: 1
						}, 8, ["label", "error"]), y(o, {
							type: "submit",
							variant: "outline",
							icon: "i-lucide-check",
							label: O(u)("polycart.cart.save"),
							"data-testid": "polycart-set-visibility"
						}, null, 8, ["label"])], 32),
						(t.members ?? []).length === 0 ? (S(), _("p", Dt, D(O(u)("polycart.cart.no_members")), 1)) : (S(), _("ul", Ot, [(S(!0), _(p, null, T(t.members, (e) => (S(), _("li", {
							key: e.id,
							class: "flex items-center justify-between gap-2 text-sm",
							"data-testid": "polycart-member"
						}, [v("span", kt, [v("span", At, D(O(a)(e.member_type)) + " #" + D(e.member_id), 1), y(s, {
							color: "primary",
							variant: "subtle",
							label: e.role
						}, null, 8, ["label"])]), y(o, {
							size: "xs",
							color: "neutral",
							variant: "ghost",
							icon: "i-lucide-user-minus",
							label: O(u)("polycart.cart.remove"),
							"data-testid": "polycart-member-remove",
							onClick: (t) => $t(e.id)
						}, null, 8, ["label", "onClick"])]))), 128))])),
						t.scope_type === null ? g("", !0) : (S(), _("form", {
							key: 2,
							class: "flex flex-wrap items-end gap-2",
							"data-testid": "polycart-share-form",
							onSubmit: j(Qt, ["prevent"])
						}, [
							y(f, {
								label: O(u)("polycart.cart.member_type"),
								hint: O(u)("polycart.cart.member_type_hint"),
								error: O(P).error("member_type")
							}, {
								default: k(() => [y(m, {
									modelValue: O(q).member_type,
									"onUpdate:modelValue": r[5] ||= (e) => O(q).member_type = e,
									class: "w-40",
									"data-testid": "polycart-share-type"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							y(f, {
								label: O(u)("polycart.cart.member_id"),
								error: O(P).error("member_id")
							}, {
								default: k(() => [y(m, {
									modelValue: O(q).member_id,
									"onUpdate:modelValue": r[6] ||= (e) => O(q).member_id = e,
									class: "w-32",
									"data-testid": "polycart-share-id"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							y(f, {
								label: O(u)("polycart.cart.role"),
								error: O(P).error("role")
							}, {
								default: k(() => [y(m, {
									modelValue: O(q).role,
									"onUpdate:modelValue": r[7] ||= (e) => O(q).role = e,
									class: "w-40",
									"data-testid": "polycart-share-role"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							y(o, {
								type: "submit",
								icon: "i-lucide-user-plus",
								label: O(u)("polycart.cart.share"),
								loading: O(P).busy.value,
								"data-testid": "polycart-share"
							}, null, 8, ["label", "loading"])
						], 32))
					])]),
					_: 2
				}, 1024),
				O(L).length > 0 ? (S(), h(d, { key: 5 }, {
					header: k(() => [v("p", jt, D(O(u)("polycart.cart.children")), 1)]),
					default: k(() => [v("ul", Mt, [(S(!0), _(p, null, T(O(L), (e) => (S(), _("li", {
						key: e.id,
						class: "flex items-center gap-2 text-sm",
						"data-testid": "polycart-child"
					}, [y(s, {
						color: "primary",
						variant: "subtle",
						label: e.type
					}, null, 8, ["label"]), y(l, {
						to: O(re)(e.id),
						class: "underline-offset-2 hover:underline"
					}, {
						default: k(() => [he(D(O(ee)(e)), 1)]),
						_: 2
					}, 1032, ["to"])]))), 128))])]),
					_: 1
				})) : g("", !0),
				y(d, { "data-testid": "polycart-details" }, {
					header: k(() => [v("p", Nt, D(O(u)("polycart.cart.details")), 1)]),
					default: k(() => [v("form", {
						class: "flex flex-col gap-3",
						"data-testid": "polycart-details-form",
						onSubmit: j(Wt, ["prevent"])
					}, [
						y(f, {
							label: O(u)("polycart.cart.label"),
							error: O(P).error("label")
						}, {
							default: k(() => [y(m, {
								modelValue: O(J).label,
								"onUpdate:modelValue": r[8] ||= (e) => O(J).label = e,
								class: "w-full",
								"data-testid": "polycart-details-label"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						y(f, {
							label: O(u)("polycart.cart.meta"),
							error: O(It) ?? O(P).error("meta")
						}, {
							default: k(() => [y(b, {
								modelValue: O(J).meta,
								"onUpdate:modelValue": r[9] ||= (e) => O(J).meta = e,
								rows: 6,
								class: "w-full font-mono",
								"data-testid": "polycart-details-meta"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						v("div", null, [y(o, {
							type: "submit",
							icon: "i-lucide-check",
							label: O(u)("polycart.cart.save"),
							loading: O(P).busy.value,
							"data-testid": "polycart-update"
						}, null, 8, ["label", "loading"])])
					], 32)]),
					_: 1
				})
			], 64))), 128)) : (S(), h(ne, {
				key: 1,
				class: "h-48"
			}))]);
		};
	}
}), P = /* @__PURE__ */ b({
	__name: "[cart]",
	setup(e) {
		let { t } = xe(), n = M(), r = m(() => String(n.params.cart ?? ""));
		return f({ title: () => t("polycart.cart.heading") }), (e, n) => {
			let ee = ce, i = s, a = u, te = c;
			return S(), h(te, null, {
				header: k(() => [y(i, { title: O(t)("polycart.cart.heading") }, {
					leading: k(() => [y(ee, {
						to: "/polycart/carts",
						color: "neutral",
						variant: "ghost",
						icon: "i-lucide-arrow-left",
						"aria-label": O(t)("polycart.cart.all_carts")
					}, null, 8, ["aria-label"])]),
					_: 1
				}, 8, ["title"])]),
				body: k(() => [y(a, {
					"screen-key": "polycart.cart",
					record: O(r)
				}, {
					default: k(() => [(S(), h(N, {
						key: O(r),
						"cart-id": O(r)
					}, null, 8, ["cart-id"]))]),
					_: 1
				}, 8, ["record"])]),
				_: 1
			});
		};
	}
});
export { P as default };
