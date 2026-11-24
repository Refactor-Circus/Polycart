import { AtriumIntegrationError as e, atriumStatus as t, useAtriumIntegration as n } from "@refactor-circus/atrium";
import { ref as r } from "vue";
// src/components/polycart.ts
var i = [
	"private",
	"scope",
	"boundary"
];
function a() {
	return n("polycart").client;
}
function o(e) {
	return `/polycart/carts/${encodeURIComponent(e)}`;
}
function s(e) {
	return e.label || e.id;
}
function c(e, t = /* @__PURE__ */ new Date()) {
	return e.expires_at !== null && new Date(e.expires_at).getTime() <= t.getTime();
}
function l(e) {
	return e.split("\\").pop() || e;
}
function u(e) {
	return e.owner_type === null ? e.session_key === null ? "—" : null : `${l(e.owner_type)} #${e.owner_id ?? ""}`;
}
function d(e) {
	return e == null ? "—" : (e / 100).toLocaleString("en-US", {
		minimumFractionDigits: 2,
		maximumFractionDigits: 2
	});
}
var f = {
	info: [
		"pending",
		"submitted",
		"awaiting",
		"awaiting_approval",
		"awaiting_payment",
		"awaiting_confirmation",
		"in_review",
		"review",
		"requested",
		"quoted"
	],
	success: [
		"open",
		"active",
		"accepted",
		"approved",
		"complete",
		"completed",
		"checked_out",
		"checkout",
		"paid",
		"fulfilled",
		"delivered",
		"shipped",
		"won",
		"done"
	],
	warning: [
		"on_hold",
		"held",
		"hold",
		"paused",
		"suspended",
		"backordered",
		"disputed"
	],
	error: [
		"declined",
		"rejected",
		"cancelled",
		"canceled",
		"failed",
		"refused",
		"revoked",
		"void",
		"voided",
		"lost"
	],
	neutral: [
		"expired",
		"abandoned",
		"merged",
		"converted",
		"closed",
		"archived",
		"refunded"
	]
};
function p(e, t = !1) {
	if (t) return "neutral";
	let n = (e ?? "").trim().toLowerCase().replace(/[\s-]/g, "_");
	for (let [e, t] of Object.entries(f)) if (t.includes(n)) return e;
	return "primary";
}
function m(e, t) {
	if (!e || e.statuses.length === 0) return [];
	let n = e.transitions;
	return n === null ? e.statuses.filter((e) => e !== t) : t === null ? [] : [...n[t] ?? []];
}
function h(t) {
	if (!t.response.ok) throw new e(t.response.status, t.error);
	return t.data;
}
function g(e) {
	return e;
}
function _() {
	let n = r({}), i = r(null), a = r(!1), o = r(!1);
	async function s(r) {
		o.value = !0, n.value = {}, i.value = null, a.value = !1;
		try {
			return await r(), !0;
		} catch (r) {
			let o = t(r), s = r instanceof e ? r.body : null;
			return o === 403 ? a.value = !0 : o === 422 && s?.errors && Object.keys(s.errors).length > 0 ? n.value = s.errors : i.value = typeof s?.message == "string" ? s.message : r instanceof Error ? r.message : String(r), !1;
		} finally {
			o.value = !1;
		}
	}
	function c(e) {
		return n.value[e]?.[0] || Object.entries(n.value).find(([t]) => t.startsWith(`${e}.`))?.[1]?.[0];
	}
	return {
		errors: n,
		message: i,
		forbidden: a,
		busy: o,
		run: s,
		error: c
	};
}
export { h as a, d as c, p as d, a as f, s as i, m as l, l as n, c as o, _ as p, o as r, g as s, i as t, u };
