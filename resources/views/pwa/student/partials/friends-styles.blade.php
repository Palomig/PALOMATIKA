{{-- Общие стили страниц «Позови друга» (8–11 и 6–7). Подключается внутри @push('styles'). --}}
  .fr-hero { text-align: center; padding: 18px 16px; border: 1px solid var(--purple-bd); border-radius: var(--r);
    background: radial-gradient(120% 90% at 50% 0%, var(--purple-bg), transparent 70%); }
  .fr-hero.gold { border-color: var(--yellow-bd); background: radial-gradient(120% 90% at 50% 0%, var(--yellow-bg), transparent 70%); }
  .fr-hero.green { border-color: var(--green-bd); background: radial-gradient(120% 90% at 50% 0%, var(--green-bg), transparent 70%); }
  .fr-sum { font-family: var(--display); font-size: 46px; line-height: 1; color: var(--purple); }
  .fr-hero.gold .fr-sum { color: var(--yellow); }
  .fr-hero.green .fr-sum { color: var(--green); }
  .fr-lbl { font-size: 12.5px; font-weight: 700; color: var(--muted); margin-top: 8px; line-height: 1.5; }
  .fr-lbl b { color: var(--text); }
  .fr-fine { font-size: 11px; color: var(--muted); font-weight: 700; text-align: center; line-height: 1.5; }
  .fr-blk { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 16px; }
  .fr-h { font-family: var(--display); font-size: 14px; margin-bottom: 12px; color: var(--text); }
  .fr-sub { font-size: 12px; color: var(--muted); font-weight: 600; line-height: 1.5; margin: -6px 0 12px; }
  .fr-step { display: flex; gap: 11px; margin-bottom: 13px; }
  .fr-step:last-child { margin-bottom: 0; }
  .fr-sn { width: 23px; height: 23px; flex: 0 0 auto; border-radius: 999px; background: var(--purple-bg); border: 1px solid var(--purple-bd);
    color: var(--purple); font-size: 11px; font-weight: 800; display: flex; align-items: center; justify-content: center; }
  .fr-st { font-size: 13px; font-weight: 700; line-height: 1.45; color: var(--text); }
  .fr-st span { display: block; color: var(--muted); font-weight: 600; font-size: 11.5px; margin-top: 2px; }
  .fr-row { display: flex; align-items: center; gap: 11px; padding: 11px 0; border-bottom: 1px solid var(--border); }
  .fr-row:last-child { border-bottom: none; padding-bottom: 0; }
  .fr-row:first-of-type { padding-top: 0; }
  .fr-av { width: 34px; height: 34px; flex: 0 0 auto; border-radius: 999px; background: var(--surface2); color: var(--muted);
    display: flex; align-items: center; justify-content: center; font-family: var(--display); font-size: 14px; }
  .fr-av.ok { background: var(--green-bg); border: 1px solid var(--green-bd); color: var(--green); }
  .fr-av.go { background: var(--accent-bg); border: 1px solid var(--accent-bd); color: var(--accent); }
  .fr-m { flex: 1; min-width: 0; }
  .fr-n { font-size: 13px; font-weight: 800; color: var(--text); }
  .fr-s { font-size: 11.5px; color: var(--muted); font-weight: 600; margin-top: 1px; }
  .fr-b { font-size: 10px; font-weight: 800; padding: 4px 8px; border-radius: 7px; flex: 0 0 auto; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
  .fr-b.ok { background: var(--green-bg); color: var(--green); border: 1px solid var(--green-bd); }
  .fr-b.go { background: var(--accent-bg); color: var(--accent); border: 1px solid var(--accent-bd); }
  .fr-lb { display: flex; align-items: center; gap: 11px; padding: 10px 0; border-bottom: 1px solid var(--border); }
  .fr-lb:last-of-type { border-bottom: none; }
  .fr-lb.you { background: var(--purple-bg); border: 1px solid var(--purple-bd); border-radius: 11px; padding: 10px; margin: 2px -6px; }
  .fr-lp { width: 26px; flex: 0 0 auto; text-align: center; font-family: var(--display); font-size: 15px; color: var(--muted); }
  .fr-ln2 { flex: 1; font-size: 13px; font-weight: 800; color: var(--text); }
  .fr-ln2 small { display: block; font-size: 10.5px; color: var(--muted); font-weight: 700; }
  .fr-lc { font-size: 11px; font-weight: 800; color: var(--purple); white-space: nowrap; }
  .fr-foot { margin-top: 12px; padding-top: 11px; border-top: 1px solid var(--border); font-size: 11.5px; color: var(--muted);
    font-weight: 700; text-align: center; line-height: 1.5; }
  .fr-empty { text-align: center; padding: 8px 10px; }
  .fr-empty-i { font-size: 30px; margin-bottom: 8px; }
  .fr-rules summary { font-size: 12.5px; font-weight: 800; color: var(--muted); cursor: pointer; list-style: none;
    display: flex; justify-content: space-between; }
  .fr-rules summary::-webkit-details-marker { display: none; }
  .fr-rules ul { margin-top: 12px; font-size: 11.5px; color: var(--muted); font-weight: 600; line-height: 1.65; }
  .fr-rules li { margin-bottom: 6px; list-style: none; padding-left: 13px; position: relative; }
  .fr-rules li::before { content: '·'; position: absolute; left: 3px; color: var(--purple); font-weight: 800; }
  .fr-rules b { color: var(--text); }
