import { t as e } from "./PolycartCartStatus-BkBDCIMd.js";
import { a as t, c as n, f as r, i, l as a, n as o, o as ee, p as te, r as s, s as c, t as l, u } from "./polycart-pC5iCRim.js";
import { UAlert as ne, UBadge as re, UButton as ie, UCard as ae, UCheckbox as oe, UDashboardNavbar as d, UDashboardPanel as f, UEmpty as se, UForm as ce, UFormField as le, UInput as ue, UInputNumber as de, ULink as fe, USelect as pe, USkeleton as me, UTable as he, UTextarea as ge, useToast as p } from "@refactor-circus/atrium/ui";
import { AtriumDetails as _e, AtriumIntegrationScreen as m, AtriumNoAccess as ve, AtriumPageHeader as ye, AtriumStat as be, AtriumState as xe, atriumStatus as h, useHead as Se } from "@refactor-circus/atrium";
import { Fragment as g, computed as _, createBlock as v, createCommentVNode as y, createElementBlock as b, createElementVNode as x, createTextVNode as S, createVNode as C, defineComponent as w, h as T, isRef as E, openBlock as D, reactive as O, ref as k, renderList as Ce, shallowRef as A, toDisplayString as j, unref as M, watch as N, withCtx as P } from "vue";
import { useI18n as F } from "vue-i18n";
import { useRoute as I, useRouter as we } from "vue-router";
// src/components/PolycartCart.vue?vue&type=script&setup=true&lang.ts
var Te = {
	class: "flex flex-col gap-5",
	"data-testid": "polycart-cart"
}, Ee = {
	class: "truncate",
	"data-testid": "polycart-cart-title"
}, De = { class: "flex flex-wrap items-center gap-2" }, Oe = { class: "grid gap-4 sm:grid-cols-3" }, ke = {
	key: 1,
	class: "text-muted"
}, Ae = { class: "font-medium" }, je = { class: "flex flex-wrap items-start gap-4" }, Me = { class: "font-medium" }, Ne = {
	key: 0,
	class: "flex items-center gap-2"
}, Pe = {
	key: 1,
	class: "font-mono"
}, Fe = {
	key: 0,
	class: "mt-1 text-xs text-error"
}, Ie = {
	key: 0,
	class: "mt-3 text-sm text-warning",
	"data-testid": "polycart-unpriced"
}, Le = { class: "font-medium" }, Re = { class: "flex flex-col gap-4" }, ze = {
	key: 0,
	class: "text-sm text-muted"
}, Be = {
	key: 1,
	class: "flex flex-col gap-2"
}, Ve = { class: "flex items-center gap-2" }, He = { class: "font-mono" }, Ue = { class: "font-medium" }, We = { class: "flex flex-col gap-2" }, Ge = { class: "font-medium" }, L = /* @__PURE__ */ w({
	__name: "PolycartCart",
	props: { cartId: {} },
	setup(d) {
		let f = d, { t: m, locale: Se } = F(), w = we(), I = p(), L = r(), R = te(), z = A(null), Ke = A(null), qe = A([]), Je = k(!1), Ye = k(!1), Xe = k(!1), Ze = k(!1), B = k(null), V = k(""), H = k(""), U = k(!0), W = k("private"), G = O({}), K = k(null), q = O({
			member_type: "",
			member_id: "",
			role: ""
		}), J = O({
			label: "",
			meta: "{}"
		}), Qe = k(null), $e = _(() => new Intl.DateTimeFormat(Se.value, {
			dateStyle: "medium",
			timeStyle: "short"
		})), et = _(() => z.value === null ? [] : [z.value]), tt = _(() => Ke.value?.find((e) => e.key === z.value?.type) ?? null), nt = _(() => Ke.value !== null && z.value !== null && tt.value === null), Y = _(() => a(tt.value, z.value?.status ?? null)), X = _(() => tt.value?.converts_to ?? []), rt = _(() => l.map((e) => ({
			value: e,
			label: m(`polycart.visibility.${e}`)
		}))), it = _(() => [
			{
				id: "item",
				header: m("polycart.cart.item")
			},
			{
				id: "unit_price",
				header: m("polycart.cart.unit_price"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			},
			{
				id: "quantity",
				header: m("polycart.cart.quantity")
			},
			{
				id: "total",
				header: m("polycart.cart.total"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			},
			{
				id: "remove",
				header: () => T("span", { class: "sr-only" }, m("polycart.cart.remove")),
				meta: { class: { td: "text-right" } }
			}
		]);
		N(Y, (e) => {
			e.includes(V.value) || (V.value = e[0] ?? "");
		}, { immediate: !0 }), N(X, (e) => {
			e.includes(H.value) || (H.value = e[0] ?? "");
		}, { immediate: !0 });
		function at(e) {
			W.value = e.visibility ?? "private", J.label = e.label ?? "", J.meta = JSON.stringify(e.meta ?? {}, null, 2);
			for (let t of e.lines ?? []) G[t.id] = t.quantity;
		}
		async function ot(e, n) {
			try {
				return t(await e.GET("/carts", { params: { query: { parent: n } } })).data;
			} catch {
				return [];
			}
		}
		async function st(e) {
			try {
				Ke.value = t(await e.GET("/types")).data;
			} catch {}
		}
		async function Z() {
			if (!L) Je.value = !0;
			else try {
				let e = t(await L.GET("/carts/{cart}", { params: { path: { cart: f.cartId } } })).data;
				z.value = e, Ye.value = !1, at(e), qe.value = (e.children_count ?? 0) > 0 ? await ot(L, e.id) : [];
			} catch (e) {
				let t = h(e);
				Ze.value = t === 403, Xe.value = t === 404, Ye.value = t !== 403 && t !== 404;
			} finally {
				Je.value = !0;
			}
		}
		async function Q(e, t) {
			return B.value = null, !L || !await R.run(() => e(L)) ? !1 : (B.value = t, await Z(), !0);
		}
		let $ = () => ({ path: { cart: f.cartId } });
		async function ct() {
			Qe.value = null;
			let e;
			try {
				e = J.meta.trim() === "" ? null : JSON.parse(J.meta);
			} catch {
				e = void 0;
			}
			e === void 0 || e !== null && (typeof e != "object" || Array.isArray(e)) ? Qe.value = m("polycart.cart.meta_invalid") : await Q(async (n) => t(await n.PATCH("/carts/{cart}", {
				params: $(),
				body: {
					label: J.label === "" ? null : J.label,
					meta: c(e)
				}
			})), m("polycart.cart.updated"));
		}
		async function lt() {
			L && window.confirm(m("polycart.cart.delete_confirm")) && (B.value = null, await R.run(async () => t(await L.DELETE("/carts/{cart}", { params: $() }))) && (I.add({
				title: m("polycart.cart.deleted"),
				color: "success"
			}), await w.push("/polycart/carts")));
		}
		async function ut() {
			window.confirm(m("polycart.cart.clear_confirm")) && await Q(async (e) => t(await e.POST("/carts/{cart}/clear", { params: $() })), m("polycart.cart.cleared"));
		}
		async function dt() {
			await Q(async (e) => t(await e.POST("/carts/{cart}/status", {
				params: $(),
				body: { status: V.value }
			})), m("polycart.cart.status_changed"));
		}
		async function ft() {
			if (!L) return;
			B.value = null;
			let e = { cart: null };
			await R.run(async () => {
				e.cart = t(await L.POST("/carts/{cart}/convert", {
					params: $(),
					body: {
						to: H.value,
						copy: U.value
					}
				})).data;
			}) && (e.cart !== null && e.cart.id !== f.cartId ? (I.add({
				title: m("polycart.cart.converted"),
				color: "success"
			}), await w.push(s(e.cart.id))) : (B.value = m("polycart.cart.converted"), await Z()));
		}
		async function pt() {
			await Q(async (e) => t(await e.PUT("/carts/{cart}/visibility", {
				params: $(),
				body: { visibility: W.value }
			})), m("polycart.cart.visibility_changed"));
		}
		async function mt(e) {
			K.value = e.id, await Q(async (n) => t(await n.PATCH("/carts/{cart}/lines", {
				params: $(),
				body: { lines: [{
					id: e.id,
					quantity: Number(G[e.id] ?? e.quantity)
				}] }
			})), m("polycart.cart.line_updated"));
		}
		async function ht(e) {
			K.value = e.id, await Q(async (n) => t(await n.DELETE("/carts/{cart}/lines", { params: {
				...$(),
				query: { "lines[]": [e.id] }
			} })), m("polycart.cart.line_removed"));
		}
		async function gt() {
			await Q(async (e) => t(await e.POST("/carts/{cart}/members", {
				params: $(),
				body: { ...q }
			})), m("polycart.cart.shared")) && Object.assign(q, {
				member_type: "",
				member_id: "",
				role: ""
			});
		}
		async function _t(e) {
			await Q(async (n) => t(await n.DELETE("/carts/{cart}/members/{member}", { params: { path: {
				cart: f.cartId,
				member: e
			} } })), m("polycart.cart.unshared"));
		}
		function vt(e) {
			return e === null ? "" : $e.value.format(new Date(e));
		}
		function yt(e) {
			return [
				{
					key: "id",
					label: m("polycart.cart.cart_id"),
					value: e.id,
					mono: !0
				},
				{
					key: "owner",
					label: m("polycart.cart.owner"),
					value: u(e) ?? m("polycart.guest")
				},
				{
					key: "source",
					label: m("polycart.cart.source"),
					value: e.source
				},
				{
					key: "expires",
					label: m("polycart.cart.expires"),
					value: e.expires_at ? vt(e.expires_at) : m("polycart.never")
				},
				{
					key: "updated",
					label: m("polycart.cart.updated_at"),
					value: vt(e.updated_at)
				},
				{
					key: "scope",
					label: m("polycart.cart.scope")
				},
				...e.parent_id ? [{
					key: "parent",
					label: m("polycart.cart.parent")
				}] : []
			];
		}
		function bt(e) {
			if (e.purchasable_type !== null) return `${o(e.purchasable_type)} #${e.purchasable_id ?? ""}`;
			let t = e.meta?.description;
			return typeof t == "string" ? t : "";
		}
		return Promise.all([L ? st(L) : null, Z()]), (t, r) => {
			let a = ve, te = me, c = ie, l = xe, u = re, d = ye, f = ne, p = be, h = fe, Se = _e, _ = ae, w = pe, T = le, O = ce, k = oe, A = se, N = de, F = he, I = ue, we = ge;
			return D(), b("div", Te, [M(Ze) ? (D(), v(a, { key: 0 })) : M(Je) ? M(Xe) ? (D(), v(l, {
				key: 2,
				icon: "i-lucide-search-x",
				title: M(m)("polycart.cart.not_found"),
				"data-testid": "polycart-not-found"
			}, {
				default: P(() => [C(c, {
					size: "sm",
					variant: "outline",
					icon: "i-lucide-arrow-left",
					to: "/polycart/carts",
					label: M(m)("polycart.cart.all_carts")
				}, null, 8, ["label"])]),
				_: 1
			}, 8, ["title"])) : M(Ye) || M(z) === null ? (D(), v(l, {
				key: 3,
				kind: "error",
				title: M(m)("polycart.cart.load_failed"),
				"data-testid": "polycart-load-error"
			}, {
				default: P(() => [C(c, {
					size: "sm",
					variant: "outline",
					icon: "i-lucide-refresh-cw",
					label: M(m)("polycart.retry"),
					"data-testid": "polycart-retry",
					onClick: r[0] ||= (e) => Z()
				}, null, 8, ["label"])]),
				_: 1
			}, 8, ["title"])) : (D(!0), b(g, { key: 4 }, Ce(M(et), (t) => (D(), b(g, { key: t.id }, [
				C(d, { title: M(i)(t) }, {
					title: P(() => [x("span", Ee, j(M(i)(t)), 1)]),
					description: P(() => [x("span", De, [C(u, {
						color: "primary",
						variant: "subtle",
						label: t.type
					}, null, 8, ["label"]), t.status ? (D(), v(e, {
						key: 0,
						status: t.status,
						expired: M(ee)(t)
					}, null, 8, ["status", "expired"])) : y("", !0)])]),
					default: P(() => [C(c, {
						size: "sm",
						color: "neutral",
						variant: "outline",
						icon: "i-lucide-archive-x",
						label: M(m)("polycart.cart.clear"),
						loading: M(R).busy.value,
						"data-testid": "polycart-clear",
						onClick: ut
					}, null, 8, ["label", "loading"]), C(c, {
						size: "sm",
						color: "error",
						variant: "outline",
						icon: "i-lucide-trash",
						label: M(m)("polycart.cart.delete"),
						loading: M(R).busy.value,
						"data-testid": "polycart-delete",
						onClick: lt
					}, null, 8, ["label", "loading"])]),
					_: 2
				}, 1032, ["title"]),
				M(B) ? (D(), v(f, {
					key: 0,
					color: "success",
					variant: "subtle",
					icon: "i-lucide-check",
					title: M(B),
					"data-testid": "polycart-notice"
				}, null, 8, ["title"])) : y("", !0),
				M(R).forbidden.value ? (D(), v(a, {
					key: 1,
					compact: "",
					description: M(m)("polycart.cart.forbidden")
				}, null, 8, ["description"])) : y("", !0),
				M(R).message.value ? (D(), v(f, {
					key: 2,
					color: "error",
					variant: "subtle",
					icon: "i-lucide-circle-alert",
					title: M(R).message.value,
					"data-testid": "polycart-error"
				}, null, 8, ["title"])) : y("", !0),
				M(nt) ? (D(), v(f, {
					key: 3,
					color: "warning",
					variant: "subtle",
					icon: "i-lucide-triangle-alert",
					title: M(m)("polycart.cart.unknown_type", { type: t.type }),
					"data-testid": "polycart-unknown-type"
				}, null, 8, ["title"])) : y("", !0),
				x("div", Oe, [
					C(p, {
						label: M(m)("polycart.cart.subtotal"),
						value: M(n)(t.subtotal),
						"data-testid": "polycart-subtotal"
					}, null, 8, ["label", "value"]),
					C(p, {
						label: M(m)("polycart.cart.quantity"),
						value: t.quantity ?? 0,
						tone: "neutral",
						"data-testid": "polycart-quantity"
					}, null, 8, ["label", "value"]),
					C(p, {
						label: M(m)("polycart.cart.lines"),
						value: t.lines?.length ?? t.lines_count ?? 0,
						tone: "neutral",
						"data-testid": "polycart-lines-count"
					}, null, 8, ["label", "value"])
				]),
				C(_, null, {
					default: P(() => [C(Se, {
						items: yt(t),
						"data-testid": "polycart-details"
					}, {
						scope: P(() => [t.scope_type ? (D(), v(u, {
							key: 0,
							color: "neutral",
							variant: "subtle",
							label: `${M(o)(t.scope_type)} #${t.scope_id ?? ""}`
						}, null, 8, ["label"])) : (D(), b("span", ke, j(M(m)("polycart.cart.unscoped")), 1))]),
						parent: P(() => [C(h, {
							to: M(s)(t.parent_id ?? ""),
							class: "atrium-mono underline-offset-2 hover:underline",
							"data-testid": "polycart-parent-link"
						}, {
							default: P(() => [S(j(t.parent_id), 1)]),
							_: 2
						}, 1032, ["to"])]),
						_: 2
					}, 1032, ["items"])]),
					_: 2
				}, 1024),
				!M(nt) && (M(Y).length > 0 || M(X).length > 0) ? (D(), v(_, {
					key: 4,
					"data-testid": "polycart-lifecycle"
				}, {
					header: P(() => [x("p", Ae, j(M(m)("polycart.cart.lifecycle")), 1)]),
					default: P(() => [x("div", je, [M(Y).length > 0 ? (D(), v(O, {
						key: 0,
						state: { status: M(V) },
						class: "flex items-start gap-2",
						"data-testid": "polycart-transition-form",
						onSubmit: dt
					}, {
						default: P(() => [C(T, {
							label: M(m)("polycart.cart.move_to"),
							name: "status",
							error: M(R).error("status")
						}, {
							default: P(() => [C(w, {
								modelValue: M(V),
								"onUpdate:modelValue": r[1] ||= (e) => E(V) ? V.value = e : null,
								items: M(Y),
								class: "w-48",
								"data-testid": "polycart-transition-status"
							}, null, 8, ["modelValue", "items"])]),
							_: 1
						}, 8, ["label", "error"]), C(c, {
							type: "submit",
							icon: "i-lucide-arrow-right",
							label: M(m)("polycart.cart.move"),
							loading: M(R).busy.value,
							class: "mt-field-label",
							"data-testid": "polycart-transition"
						}, null, 8, ["label", "loading"])]),
						_: 1
					}, 8, ["state"])) : y("", !0), M(X).length > 0 ? (D(), v(O, {
						key: 1,
						state: {
							to: M(H),
							copy: M(U)
						},
						class: "flex flex-wrap items-start gap-2",
						"data-testid": "polycart-convert-form",
						onSubmit: ft
					}, {
						default: P(() => [
							C(T, {
								label: M(m)("polycart.cart.convert_to"),
								name: "to",
								error: M(R).error("to")
							}, {
								default: P(() => [C(w, {
									modelValue: M(H),
									"onUpdate:modelValue": r[2] ||= (e) => E(H) ? H.value = e : null,
									items: M(X),
									class: "w-48",
									"data-testid": "polycart-convert-to"
								}, null, 8, ["modelValue", "items"])]),
								_: 1
							}, 8, ["label", "error"]),
							C(k, {
								modelValue: M(U),
								"onUpdate:modelValue": r[3] ||= (e) => E(U) ? U.value = e : null,
								label: M(m)("polycart.cart.keep_original"),
								class: "mt-field-label h-field items-center",
								"data-testid": "polycart-convert-copy"
							}, null, 8, ["modelValue", "label"]),
							C(c, {
								type: "submit",
								icon: "i-lucide-arrow-right-left",
								label: M(m)("polycart.cart.convert"),
								loading: M(R).busy.value,
								class: "mt-field-label",
								"data-testid": "polycart-convert"
							}, null, 8, ["label", "loading"])
						]),
						_: 1
					}, 8, ["state"])) : y("", !0)])]),
					_: 1
				})) : y("", !0),
				C(_, null, {
					header: P(() => [x("p", Me, j(M(m)("polycart.cart.lines")), 1)]),
					default: P(() => [C(F, {
						data: t.lines ?? [],
						columns: M(it),
						"data-testid": "polycart-lines"
					}, {
						empty: P(() => [C(A, {
							icon: "i-lucide-package-open",
							title: M(m)("polycart.cart.no_lines"),
							size: "xs",
							variant: "naked",
							"data-testid": "polycart-no-lines"
						}, null, 8, ["title"])]),
						"item-cell": P(({ row: e }) => [e.original.purchasable_type === null ? (D(), b("span", Ne, [C(u, {
							color: "neutral",
							variant: "subtle",
							label: M(m)("polycart.cart.custom")
						}, null, 8, ["label"]), x("span", null, j(bt(e.original)), 1)])) : (D(), b("span", Pe, j(bt(e.original)), 1))]),
						"unit_price-cell": P(({ row: e }) => [S(j(M(n)(e.original.unit_price)), 1)]),
						"quantity-cell": P(({ row: e }) => [C(O, {
							state: M(G),
							class: "flex items-center gap-2",
							"data-testid": "polycart-line-form",
							onSubmit: (t) => mt(e.original)
						}, {
							default: P(() => [C(N, {
								modelValue: M(G)[e.original.id],
								"onUpdate:modelValue": (t) => M(G)[e.original.id] = t,
								min: 0,
								size: "sm",
								class: "w-28",
								"aria-label": M(m)("polycart.cart.quantity"),
								"data-testid": "polycart-line-quantity"
							}, null, 8, [
								"modelValue",
								"onUpdate:modelValue",
								"aria-label"
							]), C(c, {
								type: "submit",
								size: "xs",
								variant: "ghost",
								icon: "i-lucide-check",
								label: M(m)("polycart.cart.update"),
								"data-testid": "polycart-line-update"
							}, null, 8, ["label"])]),
							_: 2
						}, 1032, ["state", "onSubmit"]), M(K) === e.original.id && M(R).error("lines") ? (D(), b("p", Fe, j(M(R).error("lines")), 1)) : y("", !0)]),
						"total-cell": P(({ row: e }) => [S(j(M(n)(e.original.total)), 1)]),
						"remove-cell": P(({ row: e }) => [C(c, {
							size: "xs",
							color: "neutral",
							variant: "ghost",
							icon: "i-lucide-trash",
							label: M(m)("polycart.cart.remove"),
							"data-testid": "polycart-line-remove",
							onClick: (t) => ht(e.original)
						}, null, 8, ["label", "onClick"])]),
						_: 1
					}, 8, ["data", "columns"]), (t.lines ?? []).length > 0 && t.fully_priced === !1 ? (D(), b("p", Ie, j(M(m)("polycart.cart.unpriced_lines")), 1)) : y("", !0)]),
					_: 2
				}, 1024),
				C(_, null, {
					header: P(() => [x("p", Le, j(M(m)("polycart.cart.members")), 1)]),
					default: P(() => [x("div", Re, [
						C(O, {
							state: { visibility: M(W) },
							class: "flex items-start gap-2",
							"data-testid": "polycart-visibility-form",
							onSubmit: pt
						}, {
							default: P(() => [C(T, {
								label: M(m)("polycart.cart.visible_to"),
								name: "visibility",
								error: M(R).error("visibility")
							}, {
								default: P(() => [C(w, {
									modelValue: M(W),
									"onUpdate:modelValue": r[4] ||= (e) => E(W) ? W.value = e : null,
									items: M(rt),
									class: "w-72",
									"data-testid": "polycart-visibility"
								}, null, 8, ["modelValue", "items"])]),
								_: 1
							}, 8, ["label", "error"]), C(c, {
								type: "submit",
								variant: "outline",
								icon: "i-lucide-check",
								label: M(m)("polycart.cart.save"),
								class: "mt-field-label",
								"data-testid": "polycart-set-visibility"
							}, null, 8, ["label"])]),
							_: 1
						}, 8, ["state"]),
						(t.members ?? []).length === 0 ? (D(), b("p", ze, j(M(m)("polycart.cart.no_members")), 1)) : (D(), b("ul", Be, [(D(!0), b(g, null, Ce(t.members, (e) => (D(), b("li", {
							key: e.id,
							class: "flex items-center justify-between gap-2 text-sm",
							"data-testid": "polycart-member"
						}, [x("span", Ve, [x("span", He, j(M(o)(e.member_type)) + " #" + j(e.member_id), 1), C(u, {
							color: "primary",
							variant: "subtle",
							label: e.role
						}, null, 8, ["label"])]), C(c, {
							size: "xs",
							color: "neutral",
							variant: "ghost",
							icon: "i-lucide-user-minus",
							label: M(m)("polycart.cart.remove"),
							"data-testid": "polycart-member-remove",
							onClick: (t) => _t(e.id)
						}, null, 8, ["label", "onClick"])]))), 128))])),
						t.scope_type === null ? y("", !0) : (D(), v(O, {
							key: 2,
							state: M(q),
							class: "flex flex-wrap items-start gap-2",
							"data-testid": "polycart-share-form",
							onSubmit: gt
						}, {
							default: P(() => [
								C(T, {
									label: M(m)("polycart.cart.member_type"),
									name: "member_type",
									hint: M(m)("polycart.cart.member_type_hint"),
									error: M(R).error("member_type")
								}, {
									default: P(() => [C(I, {
										modelValue: M(q).member_type,
										"onUpdate:modelValue": r[5] ||= (e) => M(q).member_type = e,
										class: "w-40",
										"data-testid": "polycart-share-type"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								]),
								C(T, {
									label: M(m)("polycart.cart.member_id"),
									name: "member_id",
									error: M(R).error("member_id")
								}, {
									default: P(() => [C(I, {
										modelValue: M(q).member_id,
										"onUpdate:modelValue": r[6] ||= (e) => M(q).member_id = e,
										class: "w-32",
										"data-testid": "polycart-share-id"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								C(T, {
									label: M(m)("polycart.cart.role"),
									name: "role",
									error: M(R).error("role")
								}, {
									default: P(() => [C(I, {
										modelValue: M(q).role,
										"onUpdate:modelValue": r[7] ||= (e) => M(q).role = e,
										class: "w-40",
										"data-testid": "polycart-share-role"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								C(c, {
									type: "submit",
									icon: "i-lucide-user-plus",
									label: M(m)("polycart.cart.share"),
									loading: M(R).busy.value,
									class: "mt-field-label",
									"data-testid": "polycart-share"
								}, null, 8, ["label", "loading"])
							]),
							_: 1
						}, 8, ["state"]))
					])]),
					_: 2
				}, 1024),
				M(qe).length > 0 ? (D(), v(_, { key: 5 }, {
					header: P(() => [x("p", Ue, j(M(m)("polycart.cart.children")), 1)]),
					default: P(() => [x("ul", We, [(D(!0), b(g, null, Ce(M(qe), (e) => (D(), b("li", {
						key: e.id,
						class: "flex items-center gap-2 text-sm",
						"data-testid": "polycart-child"
					}, [C(u, {
						color: "primary",
						variant: "subtle",
						label: e.type
					}, null, 8, ["label"]), C(h, {
						to: M(s)(e.id),
						class: "underline-offset-2 hover:underline"
					}, {
						default: P(() => [S(j(M(i)(e)), 1)]),
						_: 2
					}, 1032, ["to"])]))), 128))])]),
					_: 1
				})) : y("", !0),
				C(_, { "data-testid": "polycart-details" }, {
					header: P(() => [x("p", Ge, j(M(m)("polycart.cart.details")), 1)]),
					default: P(() => [C(O, {
						state: M(J),
						class: "flex flex-col gap-3",
						"data-testid": "polycart-details-form",
						onSubmit: ct
					}, {
						default: P(() => [
							C(T, {
								label: M(m)("polycart.cart.label"),
								name: "label",
								error: M(R).error("label")
							}, {
								default: P(() => [C(I, {
									modelValue: M(J).label,
									"onUpdate:modelValue": r[8] ||= (e) => M(J).label = e,
									class: "w-full",
									"data-testid": "polycart-details-label"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							C(T, {
								label: M(m)("polycart.cart.meta"),
								name: "meta",
								error: M(Qe) ?? M(R).error("meta")
							}, {
								default: P(() => [C(we, {
									modelValue: M(J).meta,
									"onUpdate:modelValue": r[9] ||= (e) => M(J).meta = e,
									rows: 6,
									class: "w-full font-mono",
									"data-testid": "polycart-details-meta"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							x("div", null, [C(c, {
								type: "submit",
								icon: "i-lucide-check",
								label: M(m)("polycart.cart.save"),
								loading: M(R).busy.value,
								"data-testid": "polycart-update"
							}, null, 8, ["label", "loading"])])
						]),
						_: 1
					}, 8, ["state"])]),
					_: 1
				})
			], 64))), 128)) : (D(), v(te, {
				key: 1,
				class: "h-48"
			}))]);
		};
	}
}), R = /* @__PURE__ */ w({
	__name: "[cart]",
	setup(e) {
		let { t } = F(), n = I(), r = _(() => String(n.params.cart ?? ""));
		return Se({ title: () => t("polycart.cart.heading") }), (e, n) => {
			let i = ie, a = d, o = m, ee = f;
			return D(), v(ee, null, {
				header: P(() => [C(a, { title: M(t)("polycart.cart.heading") }, {
					leading: P(() => [C(i, {
						to: "/polycart/carts",
						color: "neutral",
						variant: "ghost",
						icon: "i-lucide-arrow-left",
						"aria-label": M(t)("polycart.cart.all_carts")
					}, null, 8, ["aria-label"])]),
					_: 1
				}, 8, ["title"])]),
				body: P(() => [C(o, {
					"screen-key": "polycart.cart",
					record: M(r)
				}, {
					default: P(() => [(D(), v(L, {
						key: M(r),
						"cart-id": M(r)
					}, null, 8, ["cart-id"]))]),
					_: 1
				}, 8, ["record"])]),
				_: 1
			});
		};
	}
});
export { R as default };
