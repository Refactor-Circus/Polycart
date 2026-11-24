import { t as e } from "./PolycartCartStatus-BkBDCIMd.js";
import { a as t, c as n, f as r, i, l as a, n as o, o as ee, p as te, r as ne, s, t as c, u as re } from "./polycart-pC5iCRim.js";
import { UAlert as ie, UBadge as ae, UButton as oe, UCard as se, UCheckbox as ce, UDashboardNavbar as l, UDashboardPanel as u, UEmpty as le, UForm as ue, UFormField as de, UInput as fe, UInputNumber as pe, ULink as me, UPageHeader as he, USelect as ge, USkeleton as _e, UTable as ve, UTextarea as ye, useToast as d } from "@refactor-circus/atrium/ui";
import { AtriumIntegrationScreen as f, AtriumNoAccess as be, AtriumStat as xe, AtriumState as Se, atriumStatus as p, useHead as m } from "@refactor-circus/atrium";
import { Fragment as h, computed as g, createBlock as _, createCommentVNode as v, createElementBlock as y, createElementVNode as b, createTextVNode as x, createVNode as S, defineComponent as C, h as w, isRef as T, openBlock as E, reactive as D, ref as O, renderList as Ce, shallowRef as k, toDisplayString as A, unref as j, watch as M, withCtx as N } from "vue";
import { useI18n as P } from "vue-i18n";
import { useRoute as F, useRouter as we } from "vue-router";
// src/components/PolycartCart.vue?vue&type=script&setup=true&lang.ts
var Te = {
	class: "flex flex-col gap-5",
	"data-testid": "polycart-cart"
}, Ee = { "data-testid": "polycart-cart-title" }, De = { class: "flex flex-wrap items-center gap-2" }, Oe = { class: "grid gap-4 sm:grid-cols-3" }, ke = { class: "grid gap-x-6 gap-y-3 text-sm sm:grid-cols-[auto_1fr]" }, Ae = { class: "text-muted" }, je = { class: "font-mono" }, Me = { class: "text-muted" }, Ne = { class: "text-muted" }, Pe = { class: "text-muted" }, Fe = { class: "text-muted" }, Ie = { class: "text-muted" }, Le = {
	key: 1,
	class: "text-muted"
}, Re = { class: "text-muted" }, ze = { class: "font-medium" }, Be = { class: "flex flex-wrap items-end gap-4" }, Ve = { class: "font-medium" }, He = {
	key: 0,
	class: "flex items-center gap-2"
}, Ue = {
	key: 1,
	class: "font-mono"
}, We = {
	key: 0,
	class: "mt-1 text-xs text-error"
}, Ge = {
	key: 0,
	class: "mt-3 text-sm text-warning",
	"data-testid": "polycart-unpriced"
}, Ke = { class: "font-medium" }, qe = { class: "flex flex-col gap-4" }, Je = {
	key: 0,
	class: "text-sm text-muted"
}, Ye = {
	key: 1,
	class: "flex flex-col gap-2"
}, Xe = { class: "flex items-center gap-2" }, Ze = { class: "font-mono" }, Qe = { class: "font-medium" }, $e = { class: "flex flex-col gap-2" }, et = { class: "font-medium" }, I = /* @__PURE__ */ C({
	__name: "PolycartCart",
	props: { cartId: {} },
	setup(l) {
		let u = l, { t: f, locale: m } = P(), C = we(), F = d(), I = r(), L = te(), R = k(null), tt = k(null), nt = k([]), rt = O(!1), it = O(!1), at = O(!1), ot = O(!1), z = O(null), B = O(""), V = O(""), H = O(!0), U = O("private"), W = D({}), G = O(null), K = D({
			member_type: "",
			member_id: "",
			role: ""
		}), q = D({
			label: "",
			meta: "{}"
		}), st = O(null), ct = g(() => new Intl.DateTimeFormat(m.value, {
			dateStyle: "medium",
			timeStyle: "short"
		})), lt = g(() => R.value === null ? [] : [R.value]), J = g(() => tt.value?.find((e) => e.key === R.value?.type) ?? null), ut = g(() => tt.value !== null && R.value !== null && J.value === null), Y = g(() => a(J.value, R.value?.status ?? null)), X = g(() => J.value?.converts_to ?? []), dt = g(() => c.map((e) => ({
			value: e,
			label: f(`polycart.visibility.${e}`)
		}))), ft = g(() => [
			{
				id: "item",
				header: f("polycart.cart.item")
			},
			{
				id: "unit_price",
				header: f("polycart.cart.unit_price"),
				meta: { class: {
					th: "text-right",
					td: "text-right tabular-nums"
				} }
			},
			{
				id: "quantity",
				header: f("polycart.cart.quantity")
			},
			{
				id: "total",
				header: f("polycart.cart.total"),
				meta: { class: {
					th: "text-right",
					td: "text-right tabular-nums"
				} }
			},
			{
				id: "remove",
				header: () => w("span", { class: "sr-only" }, f("polycart.cart.remove")),
				meta: { class: { td: "text-right" } }
			}
		]);
		M(Y, (e) => {
			e.includes(B.value) || (B.value = e[0] ?? "");
		}, { immediate: !0 }), M(X, (e) => {
			e.includes(V.value) || (V.value = e[0] ?? "");
		}, { immediate: !0 });
		function pt(e) {
			U.value = e.visibility ?? "private", q.label = e.label ?? "", q.meta = JSON.stringify(e.meta ?? {}, null, 2);
			for (let t of e.lines ?? []) W[t.id] = t.quantity;
		}
		async function mt(e, n) {
			try {
				return t(await e.GET("/carts", { params: { query: { parent: n } } })).data;
			} catch {
				return [];
			}
		}
		async function ht(e) {
			try {
				tt.value = t(await e.GET("/types")).data;
			} catch {}
		}
		async function Z() {
			if (!I) rt.value = !0;
			else try {
				let e = t(await I.GET("/carts/{cart}", { params: { path: { cart: u.cartId } } })).data;
				R.value = e, it.value = !1, pt(e), nt.value = (e.children_count ?? 0) > 0 ? await mt(I, e.id) : [];
			} catch (e) {
				let t = p(e);
				ot.value = t === 403, at.value = t === 404, it.value = t !== 403 && t !== 404;
			} finally {
				rt.value = !0;
			}
		}
		async function Q(e, t) {
			return z.value = null, !I || !await L.run(() => e(I)) ? !1 : (z.value = t, await Z(), !0);
		}
		let $ = () => ({ path: { cart: u.cartId } });
		async function gt() {
			st.value = null;
			let e;
			try {
				e = q.meta.trim() === "" ? null : JSON.parse(q.meta);
			} catch {
				e = void 0;
			}
			e === void 0 || e !== null && (typeof e != "object" || Array.isArray(e)) ? st.value = f("polycart.cart.meta_invalid") : await Q(async (n) => t(await n.PATCH("/carts/{cart}", {
				params: $(),
				body: {
					label: q.label === "" ? null : q.label,
					meta: s(e)
				}
			})), f("polycart.cart.updated"));
		}
		async function _t() {
			I && window.confirm(f("polycart.cart.delete_confirm")) && (z.value = null, await L.run(async () => t(await I.DELETE("/carts/{cart}", { params: $() }))) && (F.add({
				title: f("polycart.cart.deleted"),
				color: "success"
			}), await C.push("/polycart/carts")));
		}
		async function vt() {
			window.confirm(f("polycart.cart.clear_confirm")) && await Q(async (e) => t(await e.POST("/carts/{cart}/clear", { params: $() })), f("polycart.cart.cleared"));
		}
		async function yt() {
			await Q(async (e) => t(await e.POST("/carts/{cart}/status", {
				params: $(),
				body: { status: B.value }
			})), f("polycart.cart.status_changed"));
		}
		async function bt() {
			if (!I) return;
			z.value = null;
			let e = { cart: null };
			await L.run(async () => {
				e.cart = t(await I.POST("/carts/{cart}/convert", {
					params: $(),
					body: {
						to: V.value,
						copy: H.value
					}
				})).data;
			}) && (e.cart !== null && e.cart.id !== u.cartId ? (F.add({
				title: f("polycart.cart.converted"),
				color: "success"
			}), await C.push(ne(e.cart.id))) : (z.value = f("polycart.cart.converted"), await Z()));
		}
		async function xt() {
			await Q(async (e) => t(await e.PUT("/carts/{cart}/visibility", {
				params: $(),
				body: { visibility: U.value }
			})), f("polycart.cart.visibility_changed"));
		}
		async function St(e) {
			G.value = e.id, await Q(async (n) => t(await n.PATCH("/carts/{cart}/lines", {
				params: $(),
				body: { lines: [{
					id: e.id,
					quantity: Number(W[e.id] ?? e.quantity)
				}] }
			})), f("polycart.cart.line_updated"));
		}
		async function Ct(e) {
			G.value = e.id, await Q(async (n) => t(await n.DELETE("/carts/{cart}/lines", { params: {
				...$(),
				query: { "lines[]": [e.id] }
			} })), f("polycart.cart.line_removed"));
		}
		async function wt() {
			await Q(async (e) => t(await e.POST("/carts/{cart}/members", {
				params: $(),
				body: { ...K }
			})), f("polycart.cart.shared")) && Object.assign(K, {
				member_type: "",
				member_id: "",
				role: ""
			});
		}
		async function Tt(e) {
			await Q(async (n) => t(await n.DELETE("/carts/{cart}/members/{member}", { params: { path: {
				cart: u.cartId,
				member: e
			} } })), f("polycart.cart.unshared"));
		}
		function Et(e) {
			return e === null ? "" : ct.value.format(new Date(e));
		}
		function Dt(e) {
			if (e.purchasable_type !== null) return `${o(e.purchasable_type)} #${e.purchasable_id ?? ""}`;
			let t = e.meta?.description;
			return typeof t == "string" ? t : "";
		}
		return Promise.all([I ? ht(I) : null, Z()]), (t, r) => {
			let a = be, te = _e, s = oe, c = Se, l = ae, u = he, d = ie, p = xe, m = me, g = se, C = ge, w = de, D = ue, O = ce, k = le, M = pe, P = ve, F = fe, we = ye;
			return E(), y("div", Te, [j(ot) ? (E(), _(a, { key: 0 })) : j(rt) ? j(at) ? (E(), _(c, {
				key: 2,
				icon: "i-lucide-search-x",
				title: j(f)("polycart.cart.not_found"),
				"data-testid": "polycart-not-found"
			}, {
				default: N(() => [S(s, {
					size: "sm",
					variant: "outline",
					icon: "i-lucide-arrow-left",
					to: "/polycart/carts",
					label: j(f)("polycart.cart.all_carts")
				}, null, 8, ["label"])]),
				_: 1
			}, 8, ["title"])) : j(it) || j(R) === null ? (E(), _(c, {
				key: 3,
				kind: "error",
				title: j(f)("polycart.cart.load_failed"),
				"data-testid": "polycart-load-error"
			}, {
				default: N(() => [S(s, {
					size: "sm",
					variant: "outline",
					icon: "i-lucide-refresh-cw",
					label: j(f)("polycart.retry"),
					"data-testid": "polycart-retry",
					onClick: r[0] ||= (e) => Z()
				}, null, 8, ["label"])]),
				_: 1
			}, 8, ["title"])) : (E(!0), y(h, { key: 4 }, Ce(j(lt), (t) => (E(), y(h, { key: t.id }, [
				S(u, { ui: {
					root: "border-none py-0",
					title: "truncate"
				} }, {
					title: N(() => [b("span", Ee, A(j(i)(t)), 1)]),
					description: N(() => [b("span", De, [S(l, {
						color: "primary",
						variant: "subtle",
						label: t.type
					}, null, 8, ["label"]), t.status ? (E(), _(e, {
						key: 0,
						status: t.status,
						expired: j(ee)(t)
					}, null, 8, ["status", "expired"])) : v("", !0)])]),
					links: N(() => [S(s, {
						size: "sm",
						color: "neutral",
						variant: "outline",
						icon: "i-lucide-archive-x",
						label: j(f)("polycart.cart.clear"),
						loading: j(L).busy.value,
						"data-testid": "polycart-clear",
						onClick: vt
					}, null, 8, ["label", "loading"]), S(s, {
						size: "sm",
						color: "error",
						variant: "outline",
						icon: "i-lucide-trash",
						label: j(f)("polycart.cart.delete"),
						loading: j(L).busy.value,
						"data-testid": "polycart-delete",
						onClick: _t
					}, null, 8, ["label", "loading"])]),
					_: 2
				}, 1024),
				j(z) ? (E(), _(d, {
					key: 0,
					color: "success",
					variant: "subtle",
					icon: "i-lucide-check",
					title: j(z),
					"data-testid": "polycart-notice"
				}, null, 8, ["title"])) : v("", !0),
				j(L).forbidden.value ? (E(), _(a, {
					key: 1,
					compact: "",
					description: j(f)("polycart.cart.forbidden")
				}, null, 8, ["description"])) : v("", !0),
				j(L).message.value ? (E(), _(d, {
					key: 2,
					color: "error",
					variant: "subtle",
					icon: "i-lucide-circle-alert",
					title: j(L).message.value,
					"data-testid": "polycart-error"
				}, null, 8, ["title"])) : v("", !0),
				j(ut) ? (E(), _(d, {
					key: 3,
					color: "warning",
					variant: "subtle",
					icon: "i-lucide-triangle-alert",
					title: j(f)("polycart.cart.unknown_type", { type: t.type }),
					"data-testid": "polycart-unknown-type"
				}, null, 8, ["title"])) : v("", !0),
				b("div", Oe, [
					S(p, {
						label: j(f)("polycart.cart.subtotal"),
						value: j(n)(t.subtotal),
						"data-testid": "polycart-subtotal"
					}, null, 8, ["label", "value"]),
					S(p, {
						label: j(f)("polycart.cart.quantity"),
						value: t.quantity ?? 0,
						tone: "neutral",
						"data-testid": "polycart-quantity"
					}, null, 8, ["label", "value"]),
					S(p, {
						label: j(f)("polycart.cart.lines"),
						value: t.lines?.length ?? t.lines_count ?? 0,
						tone: "neutral",
						"data-testid": "polycart-lines-count"
					}, null, 8, ["label", "value"])
				]),
				S(g, null, {
					default: N(() => [b("dl", ke, [
						b("dt", Ae, A(j(f)("polycart.cart.cart_id")), 1),
						b("dd", je, A(t.id), 1),
						b("dt", Me, A(j(f)("polycart.cart.owner")), 1),
						b("dd", null, A(j(re)(t) ?? j(f)("polycart.guest")), 1),
						b("dt", Ne, A(j(f)("polycart.cart.source")), 1),
						b("dd", null, A(t.source ?? "—"), 1),
						b("dt", Pe, A(j(f)("polycart.cart.expires")), 1),
						b("dd", null, A(t.expires_at ? Et(t.expires_at) : j(f)("polycart.never")), 1),
						b("dt", Fe, A(j(f)("polycart.cart.updated_at")), 1),
						b("dd", null, A(Et(t.updated_at)), 1),
						b("dt", Ie, A(j(f)("polycart.cart.scope")), 1),
						b("dd", null, [t.scope_type ? (E(), _(l, {
							key: 0,
							color: "neutral",
							variant: "subtle",
							label: `${j(o)(t.scope_type)} #${t.scope_id ?? ""}`
						}, null, 8, ["label"])) : (E(), y("span", Le, A(j(f)("polycart.cart.unscoped")), 1))]),
						t.parent_id ? (E(), y(h, { key: 0 }, [b("dt", Re, A(j(f)("polycart.cart.parent")), 1), b("dd", null, [S(m, {
							to: j(ne)(t.parent_id),
							class: "font-mono underline-offset-2 hover:underline",
							"data-testid": "polycart-parent-link"
						}, {
							default: N(() => [x(A(t.parent_id), 1)]),
							_: 2
						}, 1032, ["to"])])], 64)) : v("", !0)
					])]),
					_: 2
				}, 1024),
				!j(ut) && (j(Y).length > 0 || j(X).length > 0) ? (E(), _(g, {
					key: 4,
					"data-testid": "polycart-lifecycle"
				}, {
					header: N(() => [b("p", ze, A(j(f)("polycart.cart.lifecycle")), 1)]),
					default: N(() => [b("div", Be, [j(Y).length > 0 ? (E(), _(D, {
						key: 0,
						state: { status: j(B) },
						class: "flex items-end gap-2",
						"data-testid": "polycart-transition-form",
						onSubmit: yt
					}, {
						default: N(() => [S(w, {
							label: j(f)("polycart.cart.move_to"),
							name: "status",
							error: j(L).error("status")
						}, {
							default: N(() => [S(C, {
								modelValue: j(B),
								"onUpdate:modelValue": r[1] ||= (e) => T(B) ? B.value = e : null,
								items: j(Y),
								class: "w-48",
								"data-testid": "polycart-transition-status"
							}, null, 8, ["modelValue", "items"])]),
							_: 1
						}, 8, ["label", "error"]), S(s, {
							type: "submit",
							icon: "i-lucide-arrow-right",
							label: j(f)("polycart.cart.move"),
							loading: j(L).busy.value,
							"data-testid": "polycart-transition"
						}, null, 8, ["label", "loading"])]),
						_: 1
					}, 8, ["state"])) : v("", !0), j(X).length > 0 ? (E(), _(D, {
						key: 1,
						state: {
							to: j(V),
							copy: j(H)
						},
						class: "flex flex-wrap items-end gap-2",
						"data-testid": "polycart-convert-form",
						onSubmit: bt
					}, {
						default: N(() => [
							S(w, {
								label: j(f)("polycart.cart.convert_to"),
								name: "to",
								error: j(L).error("to")
							}, {
								default: N(() => [S(C, {
									modelValue: j(V),
									"onUpdate:modelValue": r[2] ||= (e) => T(V) ? V.value = e : null,
									items: j(X),
									class: "w-48",
									"data-testid": "polycart-convert-to"
								}, null, 8, ["modelValue", "items"])]),
								_: 1
							}, 8, ["label", "error"]),
							S(O, {
								modelValue: j(H),
								"onUpdate:modelValue": r[3] ||= (e) => T(H) ? H.value = e : null,
								label: j(f)("polycart.cart.keep_original"),
								class: "pb-1.5",
								"data-testid": "polycart-convert-copy"
							}, null, 8, ["modelValue", "label"]),
							S(s, {
								type: "submit",
								icon: "i-lucide-arrow-right-left",
								label: j(f)("polycart.cart.convert"),
								loading: j(L).busy.value,
								"data-testid": "polycart-convert"
							}, null, 8, ["label", "loading"])
						]),
						_: 1
					}, 8, ["state"])) : v("", !0)])]),
					_: 1
				})) : v("", !0),
				S(g, null, {
					header: N(() => [b("p", Ve, A(j(f)("polycart.cart.lines")), 1)]),
					default: N(() => [S(P, {
						data: t.lines ?? [],
						columns: j(ft),
						"data-testid": "polycart-lines"
					}, {
						empty: N(() => [S(k, {
							icon: "i-lucide-package-open",
							title: j(f)("polycart.cart.no_lines"),
							size: "xs",
							variant: "naked",
							"data-testid": "polycart-no-lines"
						}, null, 8, ["title"])]),
						"item-cell": N(({ row: e }) => [e.original.purchasable_type === null ? (E(), y("span", He, [S(l, {
							color: "neutral",
							variant: "subtle",
							label: j(f)("polycart.cart.custom")
						}, null, 8, ["label"]), b("span", null, A(Dt(e.original)), 1)])) : (E(), y("span", Ue, A(Dt(e.original)), 1))]),
						"unit_price-cell": N(({ row: e }) => [x(A(j(n)(e.original.unit_price)), 1)]),
						"quantity-cell": N(({ row: e }) => [S(D, {
							state: j(W),
							class: "flex items-center gap-2",
							"data-testid": "polycart-line-form",
							onSubmit: (t) => St(e.original)
						}, {
							default: N(() => [S(M, {
								modelValue: j(W)[e.original.id],
								"onUpdate:modelValue": (t) => j(W)[e.original.id] = t,
								min: 0,
								size: "sm",
								class: "w-28",
								"aria-label": j(f)("polycart.cart.quantity"),
								"data-testid": "polycart-line-quantity"
							}, null, 8, [
								"modelValue",
								"onUpdate:modelValue",
								"aria-label"
							]), S(s, {
								type: "submit",
								size: "xs",
								variant: "ghost",
								icon: "i-lucide-check",
								label: j(f)("polycart.cart.update"),
								"data-testid": "polycart-line-update"
							}, null, 8, ["label"])]),
							_: 2
						}, 1032, ["state", "onSubmit"]), j(G) === e.original.id && j(L).error("lines") ? (E(), y("p", We, A(j(L).error("lines")), 1)) : v("", !0)]),
						"total-cell": N(({ row: e }) => [x(A(j(n)(e.original.total)), 1)]),
						"remove-cell": N(({ row: e }) => [S(s, {
							size: "xs",
							color: "neutral",
							variant: "ghost",
							icon: "i-lucide-trash",
							label: j(f)("polycart.cart.remove"),
							"data-testid": "polycart-line-remove",
							onClick: (t) => Ct(e.original)
						}, null, 8, ["label", "onClick"])]),
						_: 1
					}, 8, ["data", "columns"]), (t.lines ?? []).length > 0 && t.fully_priced === !1 ? (E(), y("p", Ge, A(j(f)("polycart.cart.unpriced_lines")), 1)) : v("", !0)]),
					_: 2
				}, 1024),
				S(g, null, {
					header: N(() => [b("p", Ke, A(j(f)("polycart.cart.members")), 1)]),
					default: N(() => [b("div", qe, [
						S(D, {
							state: { visibility: j(U) },
							class: "flex items-end gap-2",
							"data-testid": "polycart-visibility-form",
							onSubmit: xt
						}, {
							default: N(() => [S(w, {
								label: j(f)("polycart.cart.visible_to"),
								name: "visibility",
								error: j(L).error("visibility")
							}, {
								default: N(() => [S(C, {
									modelValue: j(U),
									"onUpdate:modelValue": r[4] ||= (e) => T(U) ? U.value = e : null,
									items: j(dt),
									class: "w-72",
									"data-testid": "polycart-visibility"
								}, null, 8, ["modelValue", "items"])]),
								_: 1
							}, 8, ["label", "error"]), S(s, {
								type: "submit",
								variant: "outline",
								icon: "i-lucide-check",
								label: j(f)("polycart.cart.save"),
								"data-testid": "polycart-set-visibility"
							}, null, 8, ["label"])]),
							_: 1
						}, 8, ["state"]),
						(t.members ?? []).length === 0 ? (E(), y("p", Je, A(j(f)("polycart.cart.no_members")), 1)) : (E(), y("ul", Ye, [(E(!0), y(h, null, Ce(t.members, (e) => (E(), y("li", {
							key: e.id,
							class: "flex items-center justify-between gap-2 text-sm",
							"data-testid": "polycart-member"
						}, [b("span", Xe, [b("span", Ze, A(j(o)(e.member_type)) + " #" + A(e.member_id), 1), S(l, {
							color: "primary",
							variant: "subtle",
							label: e.role
						}, null, 8, ["label"])]), S(s, {
							size: "xs",
							color: "neutral",
							variant: "ghost",
							icon: "i-lucide-user-minus",
							label: j(f)("polycart.cart.remove"),
							"data-testid": "polycart-member-remove",
							onClick: (t) => Tt(e.id)
						}, null, 8, ["label", "onClick"])]))), 128))])),
						t.scope_type === null ? v("", !0) : (E(), _(D, {
							key: 2,
							state: j(K),
							class: "flex flex-wrap items-end gap-2",
							"data-testid": "polycart-share-form",
							onSubmit: wt
						}, {
							default: N(() => [
								S(w, {
									label: j(f)("polycart.cart.member_type"),
									name: "member_type",
									hint: j(f)("polycart.cart.member_type_hint"),
									error: j(L).error("member_type")
								}, {
									default: N(() => [S(F, {
										modelValue: j(K).member_type,
										"onUpdate:modelValue": r[5] ||= (e) => j(K).member_type = e,
										class: "w-40",
										"data-testid": "polycart-share-type"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								]),
								S(w, {
									label: j(f)("polycart.cart.member_id"),
									name: "member_id",
									error: j(L).error("member_id")
								}, {
									default: N(() => [S(F, {
										modelValue: j(K).member_id,
										"onUpdate:modelValue": r[6] ||= (e) => j(K).member_id = e,
										class: "w-32",
										"data-testid": "polycart-share-id"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								S(w, {
									label: j(f)("polycart.cart.role"),
									name: "role",
									error: j(L).error("role")
								}, {
									default: N(() => [S(F, {
										modelValue: j(K).role,
										"onUpdate:modelValue": r[7] ||= (e) => j(K).role = e,
										class: "w-40",
										"data-testid": "polycart-share-role"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								S(s, {
									type: "submit",
									icon: "i-lucide-user-plus",
									label: j(f)("polycart.cart.share"),
									loading: j(L).busy.value,
									"data-testid": "polycart-share"
								}, null, 8, ["label", "loading"])
							]),
							_: 1
						}, 8, ["state"]))
					])]),
					_: 2
				}, 1024),
				j(nt).length > 0 ? (E(), _(g, { key: 5 }, {
					header: N(() => [b("p", Qe, A(j(f)("polycart.cart.children")), 1)]),
					default: N(() => [b("ul", $e, [(E(!0), y(h, null, Ce(j(nt), (e) => (E(), y("li", {
						key: e.id,
						class: "flex items-center gap-2 text-sm",
						"data-testid": "polycart-child"
					}, [S(l, {
						color: "primary",
						variant: "subtle",
						label: e.type
					}, null, 8, ["label"]), S(m, {
						to: j(ne)(e.id),
						class: "underline-offset-2 hover:underline"
					}, {
						default: N(() => [x(A(j(i)(e)), 1)]),
						_: 2
					}, 1032, ["to"])]))), 128))])]),
					_: 1
				})) : v("", !0),
				S(g, { "data-testid": "polycart-details" }, {
					header: N(() => [b("p", et, A(j(f)("polycart.cart.details")), 1)]),
					default: N(() => [S(D, {
						state: j(q),
						class: "flex flex-col gap-3",
						"data-testid": "polycart-details-form",
						onSubmit: gt
					}, {
						default: N(() => [
							S(w, {
								label: j(f)("polycart.cart.label"),
								name: "label",
								error: j(L).error("label")
							}, {
								default: N(() => [S(F, {
									modelValue: j(q).label,
									"onUpdate:modelValue": r[8] ||= (e) => j(q).label = e,
									class: "w-full",
									"data-testid": "polycart-details-label"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							S(w, {
								label: j(f)("polycart.cart.meta"),
								name: "meta",
								error: j(st) ?? j(L).error("meta")
							}, {
								default: N(() => [S(we, {
									modelValue: j(q).meta,
									"onUpdate:modelValue": r[9] ||= (e) => j(q).meta = e,
									rows: 6,
									class: "w-full font-mono",
									"data-testid": "polycart-details-meta"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							b("div", null, [S(s, {
								type: "submit",
								icon: "i-lucide-check",
								label: j(f)("polycart.cart.save"),
								loading: j(L).busy.value,
								"data-testid": "polycart-update"
							}, null, 8, ["label", "loading"])])
						]),
						_: 1
					}, 8, ["state"])]),
					_: 1
				})
			], 64))), 128)) : (E(), _(te, {
				key: 1,
				class: "h-48"
			}))]);
		};
	}
}), L = /* @__PURE__ */ C({
	__name: "[cart]",
	setup(e) {
		let { t } = P(), n = F(), r = g(() => String(n.params.cart ?? ""));
		return m({ title: () => t("polycart.cart.heading") }), (e, n) => {
			let i = oe, a = l, o = f, ee = u;
			return E(), _(ee, null, {
				header: N(() => [S(a, { title: j(t)("polycart.cart.heading") }, {
					leading: N(() => [S(i, {
						to: "/polycart/carts",
						color: "neutral",
						variant: "ghost",
						icon: "i-lucide-arrow-left",
						"aria-label": j(t)("polycart.cart.all_carts")
					}, null, 8, ["aria-label"])]),
					_: 1
				}, 8, ["title"])]),
				body: N(() => [S(o, {
					"screen-key": "polycart.cart",
					record: j(r)
				}, {
					default: N(() => [(E(), _(I, {
						key: j(r),
						"cart-id": j(r)
					}, null, 8, ["cart-id"]))]),
					_: 1
				}, 8, ["record"])]),
				_: 1
			});
		};
	}
});
export { L as default };
