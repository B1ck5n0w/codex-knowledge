<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Familien-Radtourenplaner</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIINfQd6DgB0GfHc0p1uLlM+3uWkBZeJzk=" crossorigin="">
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" crossorigin="anonymous">
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" crossorigin="anonymous">
  <style>
    :root { color-scheme: light dark; --bg:#f4f7f2; --panel:#fff; --text:#193126; --muted:#627167; --line:#cbd6ce; --accent:#246b45; --blue:#1769aa; --return:#d36b21; }
    * { box-sizing:border-box; }
    body { margin:0; font:16px/1.45 system-ui,sans-serif; color:var(--text); background:var(--bg); }
    header { padding:18px 24px; background:var(--panel); border-bottom:1px solid var(--line); }
    h1 { margin:0; font-size:1.3rem; } header p { margin:4px 0 0; color:var(--muted); }
    main { display:grid; grid-template-columns:390px minmax(0,1fr); height:calc(100vh - 88px); min-width:0; overflow:hidden; }
    aside { min-width:0; padding:16px; background:linear-gradient(180deg,color-mix(in srgb,var(--panel) 96%,var(--accent)),var(--panel)); border-right:1px solid var(--line); overflow:auto; scrollbar-gutter:stable; }
    #map { width:100%; min-width:0; min-height:560px; height:calc(100vh - 88px); background:var(--bg); overflow:hidden; }
    /* Leaflet-Kacheln vom globalen Seiten-CSS isolieren: ohne diese Regeln können
       allgemeine Bildregeln zu Zwischenräumen zwischen Kartenkacheln führen. */
    #map .leaflet-pane, #map .leaflet-tile-container { position:absolute !important; left:0 !important; top:0 !important; }
    /* Feste Kartenebenen: Routen und Marker dürfen nie hinter den Kacheln verschwinden. */
    #map .leaflet-tile-pane { z-index:200 !important; }
    #map .leaflet-overlay-pane { z-index:400 !important; pointer-events:none; }
    #map .leaflet-marker-pane, #map .leaflet-tooltip-pane, #map .leaflet-popup-pane { z-index:650 !important; }
    #map img.leaflet-tile { position:absolute !important; left:0 !important; top:0 !important; width:256px !important; height:256px !important; min-width:256px !important; min-height:256px !important; max-width:none !important; max-height:none !important; margin:0 !important; padding:0 !important; border:0 !important; box-sizing:content-box !important; object-fit:fill !important; }
    .tour-marker-wrap, .tour-marker-wrap.leaflet-div-icon, .stop-marker-wrap, .stop-marker-wrap.leaflet-div-icon { position:absolute; overflow:visible; background:transparent; border:0; }
    .tour-marker { position:absolute; left:0; top:0; display:grid; place-items:center; width:46px; height:46px; border:4px solid #fff; border-radius:50% 50% 50% 0; background:var(--pin); color:#fff; font-size:22px; box-shadow:0 3px 9px rgba(0,0,0,.42); transform:rotate(-45deg); }
    .tour-marker > span { transform:rotate(45deg); } .tour-marker-label { position:absolute; top:53px; left:23px; display:block; width:fit-content; max-width:140px; margin:0; padding:4px 8px; overflow:hidden; border:1px solid color-mix(in srgb,var(--line) 70%,transparent); border-radius:7px; background:color-mix(in srgb,var(--panel) 96%,transparent); color:var(--text); font-size:11px; font-weight:750; line-height:1.2; text-align:center; text-overflow:ellipsis; white-space:nowrap; transform:translateX(-50%); box-shadow:0 3px 8px rgba(0,0,0,.24); pointer-events:none; }
    .tour-marker.modern-marker { border-radius:15px; font-size:16px; font-weight:850; letter-spacing:.02em; transform:none; box-shadow:0 5px 14px rgba(0,0,0,.28); } .tour-marker.modern-marker > span { transform:none; }
    .modern-marker svg { width:22px; height:22px; stroke:currentColor; stroke-width:2.4; } .modern-marker:has(svg) .marker-fallback { display:none; }
    .inline-lucide { display:inline-flex; width:17px; height:17px; margin-right:5px; vertical-align:-3px; } .inline-lucide svg { width:17px; height:17px; stroke-width:2.2; }
    .stop-badge { display:grid; place-items:center; width:38px; height:38px; border:3px solid #fff; border-radius:50%; background:#d36b21; color:#fff; font-size:19px; box-shadow:0 2px 6px rgba(0,0,0,.45); } .stop-badge.is-selected { background:#246b45; } .stop-popup button { width:auto; margin-top:8px; }
    .stop-search-progress { display:flex; align-items:center; gap:9px; margin:10px 0 4px; padding:10px; border:1px dashed color-mix(in srgb,var(--accent) 55%,var(--line)); border-radius:10px; color:var(--muted); font-size:.82rem; } .stop-search-progress::before { content:''; width:17px; height:17px; border:3px solid color-mix(in srgb,var(--accent) 22%,transparent); border-top-color:var(--accent); border-radius:50%; animation:stop-search-spin .75s linear infinite; } .action-button.is-loading svg { animation:stop-search-spin .75s linear infinite; } @keyframes stop-search-spin { to { transform:rotate(360deg); } }
    .stop-cluster { display:flex; align-items:center; justify-content:center; gap:5px; width:64px; height:42px; padding:4px 7px; border:3px solid #fff; border-radius:13px; color:#fff; box-shadow:0 3px 10px rgba(0,0,0,.35); font:800 14px/1 system-ui,sans-serif; } .stop-cluster::before { content:'⌖'; font-size:16px; } .stop-cluster small { display:block; color:#e5f2ff; font-size:9px; font-weight:750; line-height:1.05; text-align:left; }
    .planning-corridor { stroke-dasharray:8 9; animation:corridor-flow 1.8s linear infinite; } @keyframes corridor-flow { to { stroke-dashoffset:-34; } }
    #goal-overlay { position:absolute; z-index:2000; display:none; pointer-events:none; transform:translate(-50%,-100%); } #goal-overlay span { display:block; width:28px; height:28px; border:4px solid #fff; border-radius:50% 50% 50% 0; background:#246b45; box-shadow:0 2px 6px rgba(0,0,0,.5); transform:rotate(-45deg); } #goal-overlay strong { display:block; margin:5px 0 0 -36px; min-width:100px; padding:3px 6px; border-radius:4px; background:var(--panel); color:var(--text); font-size:11px; text-align:center; box-shadow:0 1px 3px rgba(0,0,0,.4); }
    #radius-overlay { position:absolute; z-index:300; display:none; pointer-events:none; border:2px solid #246b45; border-radius:50%; background:rgba(36,107,69,.10); transform:translate(-50%,-50%); }
    .sidebar-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin:0 2px 14px; } .sidebar-head strong { font-size:.95rem; letter-spacing:.01em; } .sidebar-head span { display:inline-flex; align-items:center; gap:5px; padding:4px 8px; border:1px solid var(--line); border-radius:999px; color:var(--muted); font-size:.72rem; }
    .google-usage { display:flex; align-items:center; justify-content:space-between; gap:8px; margin:-5px 2px 14px; padding:8px 10px; border:1px solid var(--line); border-radius:10px; background:color-mix(in srgb,var(--panel) 86%,transparent); color:var(--muted); font-size:.75rem; } .google-usage strong { color:var(--accent); font-size:.78rem; } .google-usage.is-blocked { border-color:#b45309; background:color-mix(in srgb,#f59e0b 14%,var(--panel)); } .google-usage.is-blocked strong { color:#b45309; }
    fieldset { margin:0 0 13px; padding:14px 12px 12px; border:1px solid color-mix(in srgb,var(--line) 85%,transparent); border-radius:14px; background:color-mix(in srgb,var(--panel) 92%,transparent); box-shadow:0 6px 18px rgba(12,35,23,.06); }
    legend { padding:0 7px; color:var(--text); font-size:.9rem; font-weight:780; letter-spacing:-.01em; } label { display:block; margin:10px 0; font-size:.88rem; } input,select,button { font:inherit; }
    .planner-step { transition:border-color .2s ease,box-shadow .2s ease,background .2s ease; } .planner-step > legend { width:100%; cursor:pointer; } .planner-step > legend::after { content:attr(data-step-summary); float:right; margin:2px 5px 0 0; color:var(--muted); font-size:.7rem; font-weight:650; } .planner-step:not(.is-active) > :not(legend) { display:none; } .planner-step:not(.is-active) { min-height:43px; padding:10px 12px 8px; box-shadow:none; } .planner-step.is-complete { border-color:color-mix(in srgb,var(--accent) 45%,var(--line)); background:color-mix(in srgb,var(--accent) 7%,var(--panel)); } .planner-step.is-active { border-color:var(--accent); box-shadow:0 0 0 2px color-mix(in srgb,var(--accent) 17%,transparent),0 6px 18px rgba(12,35,23,.08); }
    .phase-intro { margin:0 2px 13px; color:var(--muted); font-size:.82rem; line-height:1.45; } .phase-title { margin:2px 0 4px; font-size:.92rem; } .phase-note { margin:0 0 10px; color:var(--muted); font-size:.8rem; line-height:1.4; } .phase-divider { display:flex; align-items:center; gap:8px; margin:17px 1px 10px; color:var(--text); font-size:.78rem; font-weight:800; letter-spacing:.01em; } .phase-divider::before,.phase-divider::after { content:''; height:1px; flex:1; background:var(--line); }
    input[type=text], input[type=password], input[type=number], select { width:100%; min-height:40px; padding:9px 10px; border:1px solid var(--line); border-radius:9px; background:color-mix(in srgb,var(--panel) 94%,transparent); color:var(--text); transition:border-color .18s,box-shadow .18s,background .18s; }
    input:focus, select:focus { outline:0; border-color:var(--accent); box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 24%,transparent); }
    .input-action { display:flex; gap:7px; align-items:stretch; margin-top:5px; } .input-action input { min-width:0; flex:1; }
    .destination-search-results { margin:-3px 0 9px; overflow:hidden; border:1px solid var(--line); border-radius:10px; background:var(--panel); } .destination-search-result { display:grid; grid-template-columns:auto minmax(0,1fr); gap:9px; width:100%; min-height:0; margin:0; padding:9px 10px; border:0; border-bottom:1px solid var(--line); border-radius:0; color:var(--text); background:transparent; box-shadow:none; text-align:left; } .destination-search-result:last-child { border-bottom:0; } .destination-search-result:hover { background:color-mix(in srgb,var(--accent) 8%,var(--panel)); transform:none; box-shadow:none; } .destination-search-result i { width:18px; height:18px; margin-top:2px; color:var(--accent); } .destination-search-result strong { display:block; font-size:.85rem; } .destination-search-result small { display:block; margin-top:1px; overflow:hidden; color:var(--muted); font-size:.76rem; text-overflow:ellipsis; white-space:nowrap; }
    .stop-group { margin:8px 0; overflow:hidden; border:1px solid var(--line); border-radius:11px; background:color-mix(in srgb,var(--panel) 88%,transparent); } .stop-group summary { display:flex; align-items:center; justify-content:space-between; gap:9px; padding:9px 10px; cursor:pointer; color:var(--text); font-size:.83rem; font-weight:800; list-style:none; } .stop-group summary::-webkit-details-marker { display:none; } .stop-group summary::before { content:'›'; margin-right:5px; color:var(--accent); font-size:1.25rem; line-height:.75; transition:transform .18s ease; } .stop-group[open] summary::before { transform:rotate(90deg); } .stop-group summary span:first-of-type { margin-right:auto; } .stop-group-count { min-width:22px; padding:2px 6px; border-radius:999px; background:color-mix(in srgb,var(--accent) 15%,transparent); color:var(--accent); font-size:.72rem; text-align:center; } .stop-group-list { padding:0 8px 4px; }
    .icon-button { display:inline-grid; place-items:center; width:42px; min-width:42px; min-height:38px; margin:0; padding:6px; font-size:1.15rem; line-height:1; }
    .action-toolbar { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; margin:10px 0; }
    .action-button { display:flex; align-items:center; justify-content:center; gap:7px; min-height:42px; margin:0; padding:8px 9px; font-size:.82rem; font-weight:750; } .action-button .action-icon { font-size:1.05rem; line-height:1; }
    .action-button.primary { background:var(--accent); color:#fff; } .action-button.secondary { color:var(--text); background:transparent; border:1px solid var(--line); }
    .icon-button[title], .action-button[title] { position:relative; } .icon-button[title]:hover::after, .icon-button[title]:focus-visible::after, .action-button[title]:hover::after, .action-button[title]:focus-visible::after { content:attr(title); position:absolute; z-index:3000; left:50%; bottom:calc(100% + 8px); width:max-content; max-width:210px; padding:6px 8px; border-radius:6px; background:#17211b; color:#fff; font-size:.76rem; font-weight:600; line-height:1.25; text-align:center; white-space:normal; transform:translateX(-50%); box-shadow:0 2px 7px rgba(0,0,0,.35); pointer-events:none; }
    input[type=checkbox] { width:16px; height:16px; margin-right:7px; accent-color:var(--accent); vertical-align:-3px; } button { width:100%; margin-top:8px; padding:10px 11px; border:0; border-radius:9px; color:#fff; background:var(--accent); cursor:pointer; box-shadow:0 2px 5px rgba(17,70,43,.16); transition:transform .16s,filter .16s,box-shadow .16s; } button:hover:not(:disabled) { filter:brightness(1.06); box-shadow:0 5px 13px rgba(17,70,43,.2); transform:translateY(-1px); } button:active:not(:disabled) { transform:translateY(0); } button.secondary { color:var(--text); background:transparent; border:1px solid var(--line); box-shadow:none; }
    button:disabled { opacity:.5; cursor:not-allowed; } small,.hint,#status { display:block; color:var(--muted); } #status { margin:13px 2px 10px; min-height:2.8em; padding:10px 11px; border-left:3px solid var(--accent); border-radius:0 9px 9px 0; background:color-mix(in srgb,var(--accent) 9%,transparent); font-size:.8rem; }
    .metric { display:grid; grid-template-columns:1fr auto; gap:6px 12px; margin:10px 2px 16px; padding:12px; border:1px solid var(--line); border-radius:12px; background:color-mix(in srgb,var(--panel) 92%,transparent); font-size:.82rem; } .metric strong { text-align:right; color:var(--accent); font-size:.92rem; }
    .legend { display:flex; gap:12px; flex-wrap:wrap; font-size:.85rem; color:var(--muted); } .legend i { display:inline-block; width:18px; height:4px; border-radius:2px; vertical-align:middle; margin-right:4px; } .out { background:var(--blue); }.back { background:var(--return); }
    #preview { margin:10px 0 0; } #preview a { font-size:.8rem; color:var(--muted); }
    .result-list { max-height:420px; overflow:auto; margin-top:8px; } .result-list label { padding:6px; border-bottom:1px solid var(--line); font-size:.9rem; }
    .place-card { display:grid; grid-template-columns:136px minmax(0,1fr); gap:10px; align-items:stretch; padding:10px 2px; border-bottom:1px solid var(--line); } .place-card:has(input:checked) { background:color-mix(in srgb,var(--accent) 10%,transparent); } .place-card.is-alternative { opacity:.55; filter:grayscale(.55); } .place-card label { margin:0; padding:4px 0; border:0; } .place-card strong { display:block; font-size:.95rem; } .place-kind { display:block; margin-top:4px; color:var(--muted); font-size:.8rem; }
    .place-card.is-highlight { margin:7px 0; padding:9px; border:1px solid color-mix(in srgb,#d49a24 55%,var(--line)); border-radius:11px; background:linear-gradient(120deg,color-mix(in srgb,#f5c451 14%,var(--panel)),var(--panel)); } .highlight-label { display:inline-flex; align-items:center; gap:4px; margin:0 0 3px; color:#8a5a00; font-size:.72rem; font-weight:800; }
    .carousel { position:relative; overflow:hidden; min-height:106px; border-radius:7px; background:linear-gradient(135deg,#b9d7bd,#8cae93); } .carousel.large { min-height:200px; } .carousel-track { display:flex; height:100%; transition:transform .38s ease; } .carousel img, .carousel .image-fallback { flex:0 0 100%; width:100%; height:106px; object-fit:cover; } .carousel.large img, .carousel.large .image-fallback { height:200px; } .carousel .image-fallback { display:grid; place-content:center; gap:3px; padding:10px; color:#fff; text-align:center; background:linear-gradient(135deg,#315f4a,#8f6737); } .image-fallback b { font-size:2.2rem; line-height:1; } .image-fallback small { color:#fff; font-size:.76rem; font-weight:750; } .image-fallback em { color:rgba(255,255,255,.78); font-size:.68rem; font-style:normal; } .carousel-nav { position:absolute; left:0; right:0; bottom:6px; display:flex; justify-content:center; gap:5px; } .carousel-nav button { width:8px; height:8px; min-height:0; margin:0; padding:0; border:1px solid rgba(255,255,255,.9); border-radius:50%; background:rgba(16,35,25,.55); } .carousel-nav button.active { background:#fff; } .carousel-nav button:focus-visible { outline:2px solid #fff; outline-offset:2px; } .carousel-open { position:absolute; top:6px; right:6px; width:30px; height:30px; min-height:0; margin:0; padding:0; border-radius:50%; background:rgba(16,35,25,.75); color:#fff; font-size:18px; line-height:1; } .carousel-open:hover { background:var(--accent); }
    #previewDialog { width:min(680px,calc(100vw - 28px)); padding:0; border:1px solid var(--line); border-radius:12px; background:var(--panel); color:var(--text); } #previewDialog::backdrop { background:rgba(0,0,0,.55); } .preview-dialog-content { padding:16px; } .preview-dialog-head { display:flex; justify-content:space-between; gap:10px; align-items:start; } .preview-dialog-head h2 { margin:0 0 12px; font-size:1.1rem; } .preview-dialog-head button { width:34px; min-height:0; margin:0; padding:5px; background:transparent; border:1px solid var(--line); color:var(--text); } #previewDescription { margin:12px 0 4px; }
    .photo-note { margin:5px 0 0; font-size:.74rem; color:var(--muted); } .place-link { display:inline-block; margin-top:7px; font-size:.8rem; color:var(--accent); font-weight:650; } .place-link:hover { text-decoration-thickness:2px; }
    .goal-photo { margin:0 0 10px; overflow:hidden; border-radius:10px; background:var(--bg); } .goal-photo img { display:block; width:100%; max-height:260px; object-fit:cover; } .goal-photo figcaption { padding:5px 8px; color:var(--muted); font-size:.72rem; }
    .route-actions { display:grid; grid-template-columns:1fr 1fr; gap:8px; } .route-actions button { margin-top:8px; }
    .weather-timeline { display:grid; grid-template-columns:auto 1fr auto; align-items:center; gap:8px; margin-top:10px; padding:9px; border:1px solid var(--line); border-radius:10px; background:color-mix(in srgb,var(--accent) 6%,transparent); font-size:.78rem; } .weather-timeline input { min-height:0; padding:0; } .weather-summary { margin-top:10px; padding:10px; border-radius:10px; background:color-mix(in srgb,var(--panel) 92%,transparent); font-size:.82rem; } .weather-summary strong { display:block; margin-bottom:4px; } .weather-summary ul { margin:6px 0 0; padding-left:18px; } .weather-summary .rain-risk { color:#b45309; font-weight:700; } .weather-summary .clear-weather { color:var(--accent); font-weight:700; }
    .travel-setup { display:grid; grid-template-columns:repeat(3,1fr); gap:7px; margin-top:8px; } .travel-choice { position:relative; display:grid; justify-items:center; gap:5px; min-height:88px; padding:9px 4px; border:1px solid var(--line); border-radius:11px; color:var(--muted); cursor:pointer; text-align:center; transition:transform .18s ease,border-color .18s ease,background .18s ease; } .travel-choice:hover { transform:translateY(-1px); border-color:var(--accent); } .travel-choice input { position:absolute; opacity:0; pointer-events:none; } .travel-choice svg { width:26px; height:26px; color:var(--accent); stroke-width:2.4; } .travel-choice span { font-size:.74rem; font-weight:750; line-height:1.15; } .travel-choice:has(input:checked) { color:var(--text); border-color:var(--accent); background:color-mix(in srgb,var(--accent) 16%,transparent); box-shadow:0 0 0 2px color-mix(in srgb,var(--accent) 19%,transparent); } .travel-choice:has(input:focus-visible) { outline:2px solid var(--accent); outline-offset:2px; }
    .tour-profile-control { display:flex; align-items:center; gap:7px; padding:7px 9px; border:1px solid rgba(255,255,255,.9); border-radius:11px; background:rgba(24,42,31,.93); color:#fff; box-shadow:0 2px 10px rgba(0,0,0,.24); font:700 12px system-ui,sans-serif; } .tour-profile-control svg { width:18px; height:18px; color:#9fe2bb; } .tour-profile-control small { display:block; color:#d7e8dc; font-size:10px; font-weight:500; }
    .weather-marker-wrap { background:transparent; border:0; } .weather-marker { display:grid; place-items:center; width:34px; height:34px; border:3px solid #fff; border-radius:50%; background:#dc2626; color:#fff; box-shadow:0 2px 7px rgba(92,0,0,.42); animation:weather-marker-bob 1.15s ease-in-out infinite; } .weather-marker svg { width:19px; height:19px; stroke-width:2.8; } @keyframes weather-marker-bob { 50% { transform:translateY(-5px) scale(1.06); } }
    .stop-tooltip { min-width:165px; padding:8px 10px; border:0; border-radius:9px; background:rgba(20,37,27,.96); color:#fff; box-shadow:0 4px 14px rgba(0,0,0,.28); font:600 12px/1.35 system-ui,sans-serif; } .stop-tooltip strong { display:block; margin-bottom:2px; font-size:13px; } .stop-tooltip small { display:block; color:#c7ddcd; font-size:11px; font-weight:500; }
    .saved-tour { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:7px 0; border-bottom:1px solid var(--line); font-size:.85rem; } .saved-tour button { width:auto; margin:0; padding:4px 7px; font-size:.8rem; }
    .leaflet-overlay-pane svg path.route-outline { stroke:#fff; stroke-width:13px; stroke-linecap:round; stroke-linejoin:round; opacity:.92; } .leaflet-overlay-pane svg path.route-outline-return { stroke-width:0; opacity:0; }
    .leaflet-overlay-pane svg path.route-outbound { stroke:#087fc4; stroke-width:7px; stroke-linecap:round; stroke-linejoin:round; filter:drop-shadow(0 1px 2px rgba(0,0,0,.55)); }
    .leaflet-overlay-pane svg path.route-outbound-flow { stroke:#e0f5ff; stroke-width:2.5px; stroke-linecap:round; stroke-dasharray:9 22; animation:route-flow 1.05s linear infinite; pointer-events:none; }
    .leaflet-overlay-pane svg path.route-return { stroke:#e05d14; stroke-width:4px; stroke-linecap:round; stroke-linejoin:round; stroke-dasharray:10 8; animation:route-flow-return 1.4s linear infinite; filter:drop-shadow(0 1px 2px rgba(0,0,0,.55)); }
    .leaflet-overlay-pane svg path.weather-rain-segment { stroke:#dc2626; stroke-width:11px; stroke-linecap:round; stroke-linejoin:round; stroke-dasharray:5 8; animation:weather-rain-flow .9s linear infinite; filter:drop-shadow(0 1px 2px rgba(75,0,0,.5)); }
    @keyframes route-flow { to { stroke-dashoffset:-62; } } @keyframes route-flow-return { to { stroke-dashoffset:-44; } } @media (prefers-reduced-motion:reduce) { .carousel-track { transition:none; } .leaflet-overlay-pane svg path.route-outbound-flow, .leaflet-overlay-pane svg path.route-return { animation:none; } }
    @keyframes weather-rain-flow { to { stroke-dashoffset:-26; } }
    @media (prefers-reduced-motion:reduce) { .leaflet-overlay-pane svg path.weather-rain-segment { animation:none; } }
    @media (max-width:760px) { main { grid-template-columns:1fr; } aside { border-right:0; border-bottom:1px solid var(--line); } #map { height:55vh; min-height:420px; } .place-card { grid-template-columns:150px minmax(0,1fr); } }
    @media (prefers-color-scheme:dark) { :root { --bg:#17211b; --panel:#202c24; --text:#e5eee7; --muted:#b1c0b5; --line:#405047; --accent:#4fa873; } }
  </style>
</head>
<body>
  <header><h1>Familien-Radtourenplaner</h1><p>Individuelle, kinderfreundliche Radtouren mit passenden Stopps und echter Alternativroute.</p></header>
  <main>
    <aside>
      <div class="sidebar-head"><strong>Tour einrichten</strong><span>Schritt für Schritt</span></div>
      <div id="googleUsage" class="google-usage" role="status" aria-live="polite"><span>Google Places</span><strong>Kontingent wird geprüft …</strong></div>
      <fieldset class="planner-step is-active" data-step="1">
        <legend>1 · Startpunkt</legend>
        <p class="phase-intro">Wo beginnt eure Tour?</p>
        <label>Startpunkt
          <span class="input-action"><input id="startAddress" type="text" value="Pastoratstraße 13, 47608 Geldern" aria-describedby="startHelp"><button id="findAddress" class="secondary icon-button" type="button" aria-label="Startadresse suchen" title="Startadresse suchen"><i data-lucide="search" aria-hidden="true"></i></button></span>
        </label>
        <small id="startHelp">Adresse suchen, direkt auf die Karte klicken oder den aktuellen Standort verwenden.</small>
        <div class="action-toolbar"><button id="useGps" class="action-button secondary" type="button" aria-label="Aktuellen Standort verwenden" title="Aktuellen Standort verwenden"><i data-lucide="locate-fixed" aria-hidden="true"></i><span>Aktueller Standort</span></button></div>
      </fieldset>
      <fieldset class="planner-step" data-step="2">
        <legend>2 · Ziel finden</legend>
        <p class="phase-intro">Klicke einen Zielbereich in der Karte an oder nutze eine gespeicherte Idee.</p>
        <label>Ziel
          <select id="destination"></select>
        </label>
        <label>Ziel suchen
          <span class="input-action"><input id="destinationName" type="search" placeholder="z. B. Herr Lehmann, Kevelaer" autocomplete="off" aria-describedby="destinationSearchHelp"><button id="findDestinationText" class="secondary icon-button" type="button" aria-label="Nach einem Ziel suchen" title="Nach einem Ziel suchen"><i data-lucide="search" aria-hidden="true"></i></button></span>
        </label>
        <small id="destinationSearchHelp">Suche nach einem konkreten Ort, Restaurant oder Ausflugsziel in eurer Nähe.</small>
        <div id="destinationSearchResults" class="destination-search-results" aria-live="polite" hidden></div>
        <div class="action-toolbar"><button id="setDestination" class="action-button secondary" type="button" aria-label="Freies Ziel auf Karte setzen" title="Freies Ziel auf Karte setzen"><i data-lucide="crosshair" aria-hidden="true"></i><span>Auf Karte</span></button><button id="saveFavorite" class="action-button secondary" type="button" aria-label="Aktuelles Ziel als Favorit speichern" title="Aktuelles Ziel als Favorit speichern"><i data-lucide="star" aria-hidden="true"></i><span>Favorit</span></button></div>
        <div id="preview" aria-live="polite"></div>
        <div class="phase-divider"><span>Oder im Umkreis entdecken</span></div>
        <label>Suchradius: <output id="radiusValue">5 km</output><input id="radius" type="range" min="1" max="20" step="1" value="5"></label>
        <button id="findGoals" class="action-button primary" type="button" aria-label="Zielvorschläge suchen" title="Zielvorschläge suchen"><i data-lucide="search" aria-hidden="true"></i><span>Ziele suchen</span></button>
        <div id="goalList" class="result-list" aria-live="polite"></div>
      </fieldset>
      <fieldset class="planner-step" data-step="3">
        <legend>3 · Tour gestalten</legend>
        <p class="phase-intro">Wähle nur das, was für eure Fahrt wirklich wichtig ist.</p>
        <h2 class="phase-title">Familienprofil</h2>
        <div class="travel-setup" id="travelSetup" role="radiogroup" aria-label="Ausstattung für Kinder auswählen">
          <label class="travel-choice"><input type="radio" name="travelSetup" value="seat" checked><i data-lucide="baby" aria-hidden="true"></i><span>Kindersitz</span></label>
          <label class="travel-choice"><input type="radio" name="travelSetup" value="trailer"><i data-lucide="package" aria-hidden="true"></i><span>Anhänger</span></label>
          <label class="travel-choice"><input type="radio" name="travelSetup" value="both"><i data-lucide="bike" aria-hidden="true"></i><span>Sitz &amp; Anhänger</span></label>
        </div>
        <small id="travelSetupHint">Kindersitz: kurze Pausen und gut befestigte Wege im Blick.</small>
        <label><input id="avoidBusy" type="checkbox" checked> laute Landstraßen vermeiden</label>
        <label><input id="preferPaved" type="checkbox" checked> asphaltierte Wege und festen Schotter bevorzugen</label>
        <div id="stopsConfiguration" class="phase-divider"><span>Optionale Stopps</span></div>
        <p class="phase-note">Die Suche berücksichtigt nur Orte innerhalb des gewählten Korridors neben der echten Hinfahrt.</p>
        <label><input id="includeCafe" type="checkbox" checked> <i class="inline-lucide" data-lucide="coffee" aria-hidden="true"></i>Café / Eiscafé</label>
        <label><input id="includeBeer" type="checkbox" checked> <i class="inline-lucide" data-lucide="beer" aria-hidden="true"></i>Biergarten</label>
        <label><input id="includeRestaurant" type="checkbox" checked> <i class="inline-lucide" data-lucide="utensils" aria-hidden="true"></i>Restaurant</label>
        <label><input id="includeSights" type="checkbox" checked> <i class="inline-lucide" data-lucide="binoculars" aria-hidden="true"></i>Sehenswürdigkeiten</label>
        <label><input id="includePlaygrounds" type="checkbox" checked> <i class="inline-lucide" data-lucide="toy-brick" aria-hidden="true"></i>Spielplätze</label>
        <label><input id="includePicnic" type="checkbox"> <i class="inline-lucide" data-lucide="sandwich" aria-hidden="true"></i>Picknickplatz / Grillplatz</label>
        <label><input id="includeToilets" type="checkbox"> <i class="inline-lucide" data-lucide="toilet" aria-hidden="true"></i>Öffentliche Toilette</label>
        <label><input id="includeWater" type="checkbox"> <i class="inline-lucide" data-lucide="waves" aria-hidden="true"></i>See / Badestelle</label>
        <label><input id="includeAnimals" type="checkbox"> <i class="inline-lucide" data-lucide="rabbit" aria-hidden="true"></i>Tiere / Tiergehege</label>
        <label>Stopp-Korridor entlang der Hinfahrt
          <input id="stopCorridor" type="number" min="100" max="2000" step="50" value="500" inputmode="numeric" aria-describedby="stopCorridorHelp"> Meter
        </label>
        <small id="stopCorridorHelp">Zwischen 100 und 2.000 m. Vorschläge außerhalb dieses Korridors werden ausgeblendet.</small>
        <button id="findStops" class="action-button secondary" type="button" aria-label="Stopps entlang der Hinfahrt vorschlagen" title="Stopps entlang der Hinfahrt vorschlagen"><i data-lucide="compass" aria-hidden="true"></i><span>Stopps suchen</span></button>
        <div id="stopList" class="result-list" aria-live="polite"></div>
        <button id="continueToRoute" class="action-button primary" type="button" aria-label="Weiter zur Tourprüfung" title="Weiter zur Tourprüfung"><i data-lucide="arrow-right" aria-hidden="true"></i><span>Tour prüfen</span></button>
      </fieldset>
      <fieldset class="planner-step" data-step="4">
        <legend>4 · Prüfen & mitnehmen</legend>
        <p class="phase-intro">Rückweg wählen, Wetter prüfen und anschließend exportieren.</p>
        <label>Rückfahrt<select id="returnMode"><option value="direct">direkt und schnell</option><option value="alternative" selected>abweichend, ohne großen Umweg</option></select></label>
        <small id="returnRouteInfo">Die Rückweg-Variante wird bei jeder Berechnung geprüft.</small>
        <button id="calculate" class="action-button primary" type="button" aria-label="Komplette Tour berechnen" title="Komplette Tour berechnen"><i data-lucide="bike" aria-hidden="true"></i><span>Tour berechnen</span></button>
        <div class="phase-divider"><span>Wetter zur Abfahrtszeit</span></div>
        <label>Abfahrtszeit <input id="departureTime" type="datetime-local"></label>
        <div id="weatherSummary" class="weather-summary" aria-live="polite">Wetter wird nach jeder Routenberechnung automatisch für die gewählte Abfahrtszeit geprüft.</div>
        <small>Regenrisiken erscheinen als rote, animierte Abschnitte auf der Karte.</small>
        <div class="phase-divider"><span>Tour sichern</span></div>
        <div class="route-actions"><button id="saveTour" class="secondary icon-button" type="button" disabled aria-label="Tour speichern" title="Tour speichern"><i data-lucide="save" aria-hidden="true"></i></button><button id="download" class="secondary icon-button" type="button" disabled aria-label="GPX herunterladen" title="GPX herunterladen"><i data-lucide="download" aria-hidden="true"></i></button></div>
        <small>GPX erst herunterladen, wenn die Routenlinien sichtbar und plausibel sind.</small>
        <div id="savedTours" class="result-list" aria-live="polite"></div>
      </fieldset>
      <div class="legend"><span><i class="out"></i>Hinweg</span><span><i class="back"></i>Rückweg</span><span><i style="background:#dc2626"></i>Regenrisiko</span></div>
      <div id="status" role="status">Wähle auf der Karte einen Startpunkt. Die angezeigte Karte verwendet OpenStreetMap.</div>
      <div class="metric"><span>Gesamtdistanz</span><strong id="distance">—</strong><span>Fahrzeit (ohne Pausen)</span><strong id="duration">—</strong></div>
    </aside>
    <div id="map" aria-label="Karte zur Tourenplanung"></div>
  </main>
  <dialog id="goalDialog">
    <form method="dialog">
      <h2>Umkreis für Zielvorschläge</h2>
      <label>Umkreis
        <select id="dialogRadius"><option value="1">1 km</option><option value="2">2 km</option><option value="3">3 km</option><option value="5" selected>5 km</option><option value="7">7 km</option><option value="10">10 km</option><option value="15">15 km</option><option value="20">20 km</option></select>
      </label>
      <button id="confirmGoalPoint" value="confirm" type="submit">Vorschläge suchen</button>
      <button id="useGoalPoint" value="use" type="button" class="secondary">Diesen Punkt als Ziel nutzen</button>
      <button value="cancel" class="secondary" type="submit">Abbrechen</button>
    </form>
  </dialog>
  <dialog id="goalCandidateDialog">
    <div class="preview-dialog-content">
      <div class="preview-dialog-head"><h2 id="goalCandidateTitle">Zielvorschlag</h2><button id="closeGoalCandidateDialog" type="button" aria-label="Zielvorschlag schließen">×</button></div>
      <div id="goalCandidateImage"></div>
      <p id="goalCandidateKind" class="place-kind"></p>
      <p id="goalCandidateDescription"></p>
      <p id="goalCandidateTravel" class="photo-note"></p>
      <p id="goalCandidateWebsite"></p>
      <div class="route-actions"><button id="confirmGoalCandidate" type="button">✓ Ziel übernehmen</button><button id="cancelGoalCandidate" class="secondary" type="button">Weiter suchen</button></div>
    </div>
  </dialog>
  <dialog id="previewDialog">
    <div class="preview-dialog-content">
      <div class="preview-dialog-head"><h2 id="previewTitle">Vorschau</h2><button id="closePreview" type="button" aria-label="Vorschau schließen">×</button></div>
      <div id="previewLarge"></div>
      <p id="previewDescription"></p>
      <small>Es werden nur Ortsbilder aus eindeutig verknüpften Wikimedia-/Wikidata-Quellen angezeigt – keine KI-Bilder und keine heruntergeladenen Google-Maps-Fotos.</small>
    </div>
  </dialog>
  <dialog id="stopDialog">
    <div class="preview-dialog-content">
      <div class="preview-dialog-head"><h2 id="stopDialogTitle">Optionaler Stopp</h2><button id="closeStopDialog" type="button" aria-label="Stoppdialog schließen">×</button></div>
      <div id="stopDialogImage"></div>
      <p id="stopDialogKind" class="place-kind"></p>
      <p id="stopDialogDescription"></p>
      <p id="stopDialogDistance" class="photo-note"></p>
      <p id="stopDialogWebsite"></p>
      <div class="route-actions"><button id="confirmStopDialog" type="button">✓ Stopp übernehmen</button><button id="cancelStopDialog" class="secondary" type="button">Abbrechen</button></div>
    </div>
  </dialog>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js" crossorigin="anonymous"></script>
  <!-- Lucide: moderne Open-Source-SVG-Icons, MIT-Lizenz. -->
  <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" crossorigin="anonymous"></script>
  <script>
    const targets = {
      reindersmeer: { name:'Reindersmeer · See & Aussicht', lat:51.6534, lng:6.0853, wiki:'Nationaal Park De Maasduinen' },
      arcen: { name:'Arcen · Maas & Schlossgärten', lat:51.4762, lng:6.1804, wiki:'Arcen' },
      well: { name:'Well · Maasduinen & Dorfkern', lat:51.5531, lng:6.0886, wiki:'Well (Limburg)' },
      schlossWalbeck: { name:'Schloss Walbeck · Wasserburg', lat:51.50448, lng:6.22427, wiki:'Schloss Walbeck (Geldern)', tags:{historic:'castle',tourism:'attraction',website:'https://www.schloss-walbeck.de/'} }
    };
    // Zugangsdaten bleiben ausschließlich auf dem Laravel-Server.
    const routeEndpoint = '/api/route';
    async function refreshGoogleUsage() {
      const badge=document.getElementById('googleUsage');
      try {
        const response=await fetch('/api/places/usage',{headers:{Accept:'application/json'}});
        if (!response.ok) throw new Error();
        const usage=await response.json();
        const limit=Number(usage.limit), used=Number(usage.used);
        badge.classList.toggle('is-blocked',Boolean(usage.blocked));
        badge.innerHTML='<span>Google Places</span><strong>'+used+' / '+limit+' heute'+(usage.blocked?' · gesperrt':'')+'</strong>';
      } catch (_) {
        badge.innerHTML='<span>Google Places</span><strong>Kontingent lokal nicht verfügbar</strong>';
      }
    }
    const map = L.map('map', { zoomControl:true, scrollWheelZoom:true, doubleClickZoom:false, touchZoom:true, boxZoom:false }).setView([51.55, 6.20], 11);
    const goalOverlay=document.createElement('div'); goalOverlay.id='goal-overlay'; goalOverlay.innerHTML='<span></span><strong>Zielbereich</strong>'; map.getContainer().appendChild(goalOverlay); let overlayPoint=null;
    function showGoalOverlay(latlng,label) { overlayPoint=latlng; goalOverlay.querySelector('strong').textContent=label; const point=map.latLngToContainerPoint(latlng); goalOverlay.style.left=point.x+'px'; goalOverlay.style.top=point.y+'px'; goalOverlay.style.display='block'; }
    const radiusOverlay=document.createElement('div'); radiusOverlay.id='radius-overlay'; map.getContainer().appendChild(radiusOverlay); let radiusCenter=null, radiusMeters=0;
    function showRadiusOverlay(center,meters) { radiusCenter=center; radiusMeters=meters; const c=map.latLngToContainerPoint(center); const edge=map.latLngToContainerPoint(L.latLng(center.lat,center.lng).toBounds(meters).getNorthEast()); const radius=Math.max(1,Math.abs(edge.x-c.x)); radiusOverlay.style.left=c.x+'px'; radiusOverlay.style.top=c.y+'px'; radiusOverlay.style.width=(radius*2)+'px'; radiusOverlay.style.height=(radius*2)+'px'; radiusOverlay.style.display='block'; }
    map.on('move zoom resize',()=>{ if (overlayPoint) showGoalOverlay(overlayPoint,goalOverlay.querySelector('strong').textContent); if (radiusCenter) showRadiusOverlay(radiusCenter,radiusMeters); });
    let tileErrors=0;
    const tiles=L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom:19, attribution:'© OpenStreetMap-Mitwirkende' }).addTo(map);
    const topoTiles=L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', { maxZoom:17, attribution:'Kartendaten: © OpenStreetMap-Mitwirkende, SRTM | Darstellung: © OpenTopoMap' });
    const satelliteTiles=L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom:19, attribution:'Tiles © Esri' });
    L.control.layers({'Standard':tiles,'Topografie':topoTiles,'Luftbild':satelliteTiles}, {}, {position:'topright'}).addTo(map);
    const clusterOptions=(label,color)=>({maxClusterRadius:54,disableClusteringAtZoom:15,spiderfyOnMaxZoom:true,showCoverageOnHover:false,zoomToBoundsOnClick:true,spiderLegPolylineOptions:{weight:2,color,opacity:.75},iconCreateFunction:cluster=>L.divIcon({className:'stop-cluster-wrap',html:'<span class="stop-cluster" style="background:'+color+'" title="'+cluster.getChildCount()+' '+label+' – zum Auflösen anklicken"><b>'+cluster.getChildCount()+'</b><small>'+label+'<br>antippen</small></span>',iconSize:[64,42],iconAnchor:[32,21]})});
    let weatherRiskLayer=L.layerGroup().addTo(map), weatherRouteLayer=L.layerGroup().addTo(map), stopLayer=(L.markerClusterGroup?L.markerClusterGroup(clusterOptions('Stopps','#246b45')):L.layerGroup()).addTo(map), goalLayer=(L.markerClusterGroup?L.markerClusterGroup(clusterOptions('Ziele','#3b82f6')):L.layerGroup()).addTo(map);
    tiles.on('tileerror', () => { tileErrors++; if (tileErrors===3) status.textContent='Die Kartenkacheln konnten nicht geladen werden. Bitte Internetverbindung oder Werbe-/Tracking-Blocker prüfen.'; });
    window.addEventListener('load', () => setTimeout(() => map.invalidateSize(), 100));
    window.addEventListener('resize', () => map.invalidateSize());
    let start = null, startMarker = null, destinationMarker = null, pendingMarker = null, goalArea = null, outbound = null, returnRoute = null, planningCorridor = null, routePoints = [], customDestination = null, forwardCoords = [], returnCoords = [], goalMarkers = [], stopMarkers = [], selectedStops = [], goalSearchCenter = null, mapMode = null, stopSearchVersion = 0, routeCalculationVersion = 0, activeStopCandidate = null, stopCandidateState = [];
    const previewPlaces = new Map();
    const status = document.getElementById('status');
    let highestUnlockedStep=1;
    const stepSummaries={1:'Start',2:'Ziel',3:'Tour',4:'Fertig'};
    function activatePlannerStep(step) { highestUnlockedStep=Math.max(highestUnlockedStep,step); document.querySelectorAll('.planner-step').forEach(fieldset=>{ const number=Number(fieldset.dataset.step); const active=number===step; fieldset.classList.toggle('is-active',active); fieldset.classList.toggle('is-complete',number<step); const legend=fieldset.querySelector('legend'); legend.dataset.stepSummary=number<step?'✓ '+stepSummaries[number]:number===step?'Aktiv':'Folgt'; legend.setAttribute('aria-expanded',String(active)); }); const active=document.querySelector('.planner-step[data-step="'+step+'"]'); if (active) setTimeout(()=>active.scrollIntoView({block:'nearest',behavior:'smooth'}),0); }
    function setupPlannerSteps() { document.querySelectorAll('.planner-step').forEach(fieldset=>{ const number=Number(fieldset.dataset.step), legend=fieldset.querySelector('legend'); legend.tabIndex=0; legend.setAttribute('role','button'); const open=()=>{ if (number<=highestUnlockedStep) activatePlannerStep(number); }; legend.addEventListener('click',open); legend.addEventListener('keydown',event=>{ if (event.key==='Enter'||event.key===' ') { event.preventDefault(); open(); } }); }); activatePlannerStep(1); }
    const travelProfiles={seat:{label:'Kindersitz',hint:'Kindersitz: kurze Pausen und gut befestigte Wege im Blick.',icon:'baby'},trailer:{label:'Fahrradanhänger',hint:'Anhänger: feste, ausreichend breite Wege bevorzugen.',icon:'package'},both:{label:'Kindersitz & Anhänger',hint:'Sitz & Anhänger: besonders ruhige, breite und feste Wege bevorzugen.',icon:'bike'}};
    let tourProfileControl;
    function selectedTravelProfile() { return document.querySelector('input[name="travelSetup"]:checked')?.value || 'seat'; }
    function renderTravelSetup() { const key=selectedTravelProfile(), profile=travelProfiles[key]; document.getElementById('travelSetupHint').textContent=profile.hint; localStorage.setItem('maasduinen-travel-setup',key); if (tourProfileControl) tourProfileControl.innerHTML='<i data-lucide="'+profile.icon+'" aria-hidden="true"></i><span>'+profile.label+'<small>Familien-Tourprofil</small></span>'; setTimeout(()=>window.lucide?.createIcons({attrs:{'aria-hidden':'true'}}),0); }
    const profileControl=L.control({position:'bottomright'}); profileControl.onAdd=()=>{ tourProfileControl=L.DomUtil.create('div','tour-profile-control'); L.DomEvent.disableClickPropagation(tourProfileControl); return tourProfileControl; }; profileControl.addTo(map);
    const destination = document.getElementById('destination');
    const destinationName = document.getElementById('destinationName');
    const departureTime=document.getElementById('departureTime');
    const defaultDeparture=new Date(Date.now()+30*60000); defaultDeparture.setSeconds(0,0); departureTime.value=defaultDeparture.getFullYear()+'-'+String(defaultDeparture.getMonth()+1).padStart(2,'0')+'-'+String(defaultDeparture.getDate()).padStart(2,'0')+'T'+String(defaultDeparture.getHours()).padStart(2,'0')+':'+String(defaultDeparture.getMinutes()).padStart(2,'0');
    function stopCorridorMeters() { const field=document.getElementById('stopCorridor'); const value=Math.max(100,Math.min(2000,Number(field.value)||500)); field.value=value; return value; }
    function favorites() { try { return JSON.parse(localStorage.getItem('maasduinen-favorites') || '{}'); } catch { return {}; } }
    function savedTours() { try { return JSON.parse(localStorage.getItem('maasduinen-saved-tours') || '[]'); } catch { return []; } }
    function hashText(text) { let h=2166136261; for(let i=0;i<text.length;i++) { h^=text.charCodeAt(i); h=Math.imul(h,16777619); } return (h>>>0).toString(36); }
    function cacheRead(bucket,key,maxAge) { try { const entry=(JSON.parse(localStorage.getItem(bucket)||'{}'))[key]; return entry && Date.now()-entry.saved<maxAge ? entry.value : null; } catch { return null; } }
    function cacheWrite(bucket,key,value,maxEntries=80) { try { const all=JSON.parse(localStorage.getItem(bucket)||'{}'); all[key]={saved:Date.now(),value}; const keys=Object.keys(all).sort((a,b)=>all[b].saved-all[a].saved).slice(0,maxEntries); localStorage.setItem(bucket,JSON.stringify(Object.fromEntries(keys.map(k=>[k,all[k]])))); } catch {} }
    function placeKind(tags={}) { if (tags.historic==='castle' || tags.historic==='manor' || tags.historic==='ruins') return 'Schloss / Historische Anlage'; if (tags.leisure==='playground') return 'Spielplatz'; if (tags.tourism==='viewpoint') return 'Aussichtspunkt'; if (tags.amenity==='biergarten') return 'Biergarten'; if (tags.amenity==='cafe' || tags.amenity==='ice_cream') return 'Café / Eiscafé'; if (tags.amenity==='restaurant') return 'Restaurant'; if (tags.amenity==='toilets') return 'Öffentliche Toilette'; if (tags.tourism==='picnic_site' || tags.leisure==='picnic_table' || tags.amenity==='bbq') return 'Picknick / Grillplatz'; if (tags.leisure==='swimming_area' || tags.natural==='beach' || tags.natural==='water') return 'See / Badestelle'; if (tags.tourism==='zoo' || tags.animal) return 'Tiere / Tiergehege'; return 'Ausflugsziel'; }
    function placeEmoji(tags={}) { if (tags.historic==='castle' || tags.historic==='manor' || tags.historic==='ruins') return '🏰'; if (tags.leisure==='playground') return '🛝'; if (tags.tourism==='viewpoint') return '🔭'; if (tags.amenity==='biergarten') return '🍺'; if (tags.amenity==='cafe' || tags.amenity==='ice_cream') return '☕'; if (tags.amenity==='restaurant') return '🍴'; if (tags.amenity==='toilets') return '🚻'; if (tags.tourism==='picnic_site' || tags.leisure==='picnic_table' || tags.amenity==='bbq') return '🧺'; if (tags.leisure==='swimming_area' || tags.natural==='beach' || tags.natural==='water') return '🌊'; if (tags.tourism==='zoo' || tags.animal) return '🐾'; return '📍'; }
    function carouselHtml(id, label, tags={}, large=false, expandable=true) { const emoji=placeEmoji(tags), kind=placeKind(tags); return '<div class="carousel'+(large?' large':'')+'" id="'+id+'" aria-label="Vorschaubilder zu '+safe(label)+'">'+(expandable?'<button class="carousel-open" data-open-preview="'+id+'" type="button" aria-label="Große Vorschau zu '+safe(label)+' öffnen">⤢</button>':'')+'<div class="carousel-track"><span class="image-fallback" aria-hidden="true"><b>'+emoji+'</b><small>'+safe(kind)+'</small><em>Kein verifiziertes Foto</em></span></div><div class="carousel-nav" aria-label="Bildnavigation"><button class="active" type="button" aria-label="Bild 1 anzeigen"></button></div></div>'; }
    function renderCarousel(id, images, label, tags={}) { const root=document.getElementById(id); if (!root || !images.length) return; const track=root.querySelector('.carousel-track'), nav=root.querySelector('.carousel-nav'); track.innerHTML=images.map((src,i)=>'<img src="'+safe(src)+'" alt="'+safe(label)+' · Bild '+(i+1)+'" loading="lazy">').join(''); nav.innerHTML=images.map((_,i)=>'<button type="button" class="'+(i===0?'active':'')+'" aria-label="Bild '+(i+1)+' anzeigen"></button>').join(''); [...nav.querySelectorAll('button')].forEach((button,i)=>button.addEventListener('click',()=>{ track.style.transform='translateX(-'+(i*100)+'%)'; [...nav.querySelectorAll('button')].forEach((dot,n)=>dot.classList.toggle('active',n===i)); })); }
    async function commonsFiles(fileNames) { if (!fileNames.length) return []; const titles=fileNames.map(name=>'File:'+name.replace(/^File:/i,'')).join('|'); const endpoint='https://commons.wikimedia.org/w/api.php?action=query&titles='+encodeURIComponent(titles)+'&prop=imageinfo&iiprop=url&iiurlwidth=720&format=json&origin=*'; const data=await (await fetch(endpoint)).json(); return Object.values(data.query?.pages || {}).map(p=>p.imageinfo?.[0]?.thumburl || p.imageinfo?.[0]?.url).filter(Boolean).slice(0,3); }
    async function commonsImages(place) { const tags=place.tags || {}; const cacheKey=hashText([tags.wikidata,tags.wikimedia_commons,tags.image].filter(Boolean).join('|')); if (!cacheKey) return []; const cached=cacheRead('maasduinen-verified-media-v1',cacheKey,1000*60*60*24*21); if (cached) return cached; try { const direct=tags.image || ''; if (/^(https:\/\/upload\.wikimedia\.org|https:\/\/commons\.wikimedia\.org\/wiki\/Special:FilePath)/.test(direct)) { cacheWrite('maasduinen-verified-media-v1',cacheKey,[direct]); return [direct]; } let files=[]; if (tags.wikidata) { const data=await (await fetch('https://www.wikidata.org/w/api.php?action=wbgetentities&ids='+encodeURIComponent(tags.wikidata)+'&props=claims&format=json&origin=*')).json(); const claim=data.entities?.[tags.wikidata]?.claims?.P18?.[0]?.mainsnak?.datavalue?.value; if (claim) files=[claim]; }
        if (!files.length && /^File:/i.test(tags.wikimedia_commons || '')) files=[tags.wikimedia_commons];
        if (!files.length && /^Category:/i.test(tags.wikimedia_commons || '')) { const category='https://commons.wikimedia.org/w/api.php?action=query&generator=categorymembers&gcmtitle='+encodeURIComponent(tags.wikimedia_commons)+'&gcmtype=file&gcmlimit=3&prop=imageinfo&iiprop=url&iiurlwidth=720&format=json&origin=*'; const data=await (await fetch(category)).json(); const images=Object.values(data.query?.pages || {}).map(p=>p.imageinfo?.[0]?.thumburl || p.imageinfo?.[0]?.url).filter(Boolean).slice(0,3); cacheWrite('maasduinen-verified-media-v1',cacheKey,images); return images; }
        const images=await commonsFiles(files); cacheWrite('maasduinen-verified-media-v1',cacheKey,images); return images;
      } catch { cacheWrite('maasduinen-verified-media-v1',cacheKey,[]); return []; } }
    async function hydrateCarousels(rows, prefix) { if (prefix==='stop-image') groupStopCards(rows,document.getElementById('stopList')); const queue=rows.slice(0,12); for (let i=0;i<queue.length;i+=3) await Promise.all(queue.slice(i,i+3).map(async (place,n)=>{ const index=i+n, images=await commonsImages(place); renderCarousel(prefix+'-'+index,images,place.name,place.tags); })); }
    function renderDestinations(selected) {
      const all = {...targets, ...favorites()}; const current = selected || destination.value || '';
      destination.innerHTML = '<option value="" disabled>Bitte Zielbereich oder Ziel wählen</option>'+Object.entries(all).map(([id, place]) => '<option value="'+id+'">'+place.name+'</option>').join('') + '<option value="custom">Freies Ziel auf der Karte</option>';
      destination.value = current in all || current === 'custom' ? current : '';
    }
    function destinationData() { const all = {...targets, ...favorites()}; return destination.value === 'custom' ? customDestination : (destination.value ? all[destination.value] : null); }
    function safe(text) { const el=document.createElement('span'); el.textContent=String(text); return el.innerHTML; }
    function websiteLink(tags={}) { const url=tags.website || tags['contact:website'] || ''; return /^https?:\/\//i.test(url) ? '<a class="place-link" href="'+safe(url)+'" target="_blank" rel="noopener noreferrer">Offizielle Website ↗</a>' : ''; }
    function photoNote(tags={}, place={}) { if (place.googlePhoto?.url) return '<small class="photo-note">Ortsfoto über Google Places geladen</small>'; return tags.wikidata || tags.wikimedia_commons || /^(https:\/\/upload\.wikimedia\.org|https:\/\/commons\.wikimedia\.org\/wiki\/Special:FilePath)/.test(tags.image || '') ? '<small class="photo-note">Ortsbild nur aus verifizierter Wikimedia-Quelle</small>' : '<small class="photo-note">Google-Details werden beim Öffnen dieses Stopps geprüft</small>'; }
    function marker(label, point, color, kind='Ziel', layer=map) {
      let iconName=kind==='Start'?'bike':kind==='Ziel'?'flag-triangle-right':kind==='Alternative'?'circle-dashed':'map-pin'; let symbol=kind==='Start'?'S':kind==='Ziel'?'Z':kind==='Alternative'?'○':'+'; let markerLabel=kind;
      if (kind==='Vorschlag' || kind==='Alternative') { const category=placeKind(point.tags || {}); const style={
        'Restaurant':{symbol:'🍴',icon:'utensils',color:'#d97706'}, 'Biergarten':{symbol:'🍺',icon:'beer',color:'#b45309'}, 'Café / Eiscafé':{symbol:'☕',icon:'coffee',color:'#db2777'}, 'Spielplatz':{symbol:'🛝',icon:'toy-brick',color:'#7c3aed'}, 'Aussichtspunkt':{symbol:'🔭',icon:'binoculars',color:'#0284c7'}, 'See / Badestelle':{symbol:'🌊',icon:'waves',color:'#0369a1'}, 'Picknick / Grillplatz':{symbol:'🧺',icon:'sandwich',color:'#7c5e2a'}, 'Öffentliche Toilette':{symbol:'🚻',icon:'toilet',color:'#64748b'}, 'Tiere / Tiergehege':{symbol:'🐾',icon:'rabbit',color:'#a16207'}, 'Schloss / Historische Anlage':{symbol:'🏰',icon:'castle',color:'#7c3f1d'}, 'Ausflugsziel':{symbol:'★',icon:'landmark',color:'#0f766e'}
      }[category] || {symbol:'📍',icon:'map-pin',color:'#0f766e'}; symbol=style.symbol; iconName=style.icon; color=kind==='Alternative'?'#718078':style.color; markerLabel=kind==='Alternative'?'Alternative · '+category:category; }
      if (kind==='Start') markerLabel='Start';
      if (kind==='Ziel') { const shortName=String(label).split(/[·,]/)[0].replace(/^Ziel:\s*/i,'').trim(); markerLabel='Ziel · '+(shortName || 'Ziel'); }
      const icon=L.divIcon({className:'tour-marker-wrap',html:'<span class="tour-marker modern-marker" style="--pin:'+color+'"><i data-lucide="'+iconName+'" aria-hidden="true"></i><span class="marker-fallback">'+symbol+'</span></span><span class="tour-marker-label">'+safe(markerLabel)+'</span>',iconSize:[130,82],iconAnchor:[23,46]}); const pin=L.marker([point.lat,point.lng],{icon,keyboard:true,zIndexOffset:1200}).addTo(layer).bindPopup('<strong>'+safe(label)+'</strong><br><small>'+safe(markerLabel)+'</small>'); const applyAccessibleName=()=>{ const element=pin.getElement(); if (element) element.setAttribute('aria-label',String(label)+' · '+markerLabel); }; applyAccessibleName(); pin.on('add',applyAccessibleName); setTimeout(()=>window.lucide?.createIcons({attrs:{'aria-hidden':'true'}}),0); return pin;
    }
    function stopIcon(tags={}) { return placeEmoji(tags); }
    function stopTooltip(place) { const tags=place.tags || {}, details=[Math.round(place.routeDistance || 0)+' m neben der Hinfahrt']; if (tags.opening_hours) details.push('Öffnungszeiten: '+safe(tags.opening_hours)); if (tags.wheelchair) details.push(tags.wheelchair==='yes'?'barrierearm markiert':'Barrierefreiheit vor Ort prüfen'); return '<strong>'+safe(place.name)+'</strong><small>'+safe(placeKind(tags))+' · '+details.join('<br>')+'</small>'; }
    function stopMarker(place,index,input,version) { const makeIcon=selected=>L.divIcon({className:'stop-marker-wrap',html:'<span class="stop-badge'+(selected?' is-selected':'')+'">'+(selected?'✓':stopIcon(place.tags))+'</span>',iconSize:[38,38],iconAnchor:[19,19]}); const pin=L.marker([place.lat,place.lng],{icon:makeIcon(false),keyboard:true,title:place.name,zIndexOffset:1100}).addTo(stopLayer).bindTooltip(stopTooltip(place),{direction:'top',offset:[0,-18],className:'stop-tooltip',opacity:1}); const open=()=>openStopDialog(place,input,pin); const bindDialog=()=>{ const element=pin.getElement(); if (element && !element.dataset.stopDialogBound) { element.dataset.stopDialogBound='true'; element.addEventListener('click',event=>{ event.stopPropagation(); open(); }); } }; bindDialog(); pin.on('add',bindDialog); pin.on('click',open); input.addEventListener('change',()=>{ pin.setIcon(makeIcon(input.checked)); setTimeout(bindDialog,0); }); return pin; }
    function confirmedStopMarker(place) { const icon=L.divIcon({className:'stop-marker-wrap',html:'<span class="stop-badge is-selected">✓</span>',iconSize:[38,38],iconAnchor:[19,19]}); return L.marker([place.lat,place.lng],{icon,keyboard:true,title:place.name,zIndexOffset:1100}).addTo(stopLayer).bindPopup('<strong>'+safe(place.name)+'</strong><br><small>In dieser Tour eingeplanter Stopp</small>'); }
    function setStart(latlng) {
      start = { lat:latlng.lat, lng:latlng.lng };
      if (startMarker) map.removeLayer(startMarker);
      startMarker = marker('Startpunkt', start, '#1769aa', 'Start');
      updateDestination();
      activatePlannerStep(2);
      status.textContent = 'Startpunkt gesetzt. Lege nun in Schritt 2 einen Zielbereich fest oder wähle ein Ziel aus.';
    }
    function clearStopMarkers() { stopLayer.clearLayers(); stopMarkers=[]; }
    function clearStopSuggestions() { clearStopMarkers(); stopCandidateState=[]; selectedStops=[]; document.getElementById('stopList').innerHTML=''; }
    function updateDestination(preserveStops=false) {
      if (!preserveStops) clearStopSuggestions();
      const end = destinationData();
      if (!end) { if (destinationMarker) map.removeLayer(destinationMarker); return; }
      if (destinationMarker) map.removeLayer(destinationMarker);
      destinationMarker = marker(end.name, end, '#246b45', 'Ziel');
      updatePlanningCorridor();
      if (start) setTimeout(focusPlanningArea,0);
      loadPreview(end);
    }
    function previewDescription(place) { const tags=place.tags || {}; if (tags.description) return String(tags.description).slice(0,240); const kind=placeKind(tags); const details=[]; if (tags.opening_hours) details.push('Öffnungszeiten: '+tags.opening_hours); if (tags.website) details.push('Weitere Informationen sind online hinterlegt.'); return details.join(' ') || kind+' im gewählten Tourbereich. Eignung für Kinder und Anhänger bitte vor Ort prüfen.'; }
    function openStopDialog(place,input,pin) {
      activeStopCandidate={place,input,pin}; const dialog=document.getElementById('stopDialog');
      const render=()=>{ document.getElementById('stopDialogTitle').textContent=place.name; document.getElementById('stopDialogKind').textContent=placeEmoji(place.tags)+' '+placeKind(place.tags)+(place.googleRating?' · ★ '+place.googleRating+' ('+place.googleRatingCount+')':''); document.getElementById('stopDialogDescription').textContent=previewDescription(place); document.getElementById('stopDialogDistance').textContent='Etwa '+Math.round(place.routeDistance || 0)+' m neben der berechneten Hinfahrt.'; document.getElementById('stopDialogWebsite').innerHTML=websiteLink(place.tags); document.getElementById('stopDialogImage').innerHTML=place.googlePhoto?.url?'<figure class="goal-photo"><img src="'+safe(place.googlePhoto.url)+'" alt="'+safe(place.name)+'"><figcaption>'+safe(place.googlePhoto.attribution ? 'Foto: '+place.googlePhoto.attribution : 'Foto: Google Places')+'</figcaption></figure>':''; };
      render(); if (!dialog.open) dialog.showModal();
      enrichStopFromGoogle(place).then(render).catch(error=>{ document.getElementById('stopDialogDescription').textContent='Basisdaten geladen. '+(error.message || 'Details konnten nicht geladen werden.'); });
    }
    function closeStopDialog(confirmed=false) {
      const current=activeStopCandidate; const dialog=document.getElementById('stopDialog');
      if (dialog.open) dialog.close(); activeStopCandidate=null;
      if (confirmed && current) { current.input.checked=true; current.input.dispatchEvent(new Event('change')); current.pin.closeTooltip(); }
    }
    async function openPreview(id) { const place=previewPlaces.get(id); if (!place) return; const dialog=document.getElementById('previewDialog'); document.getElementById('previewTitle').textContent=place.name; document.getElementById('previewDescription').textContent=previewDescription(place); document.getElementById('previewLarge').innerHTML=carouselHtml('detail-carousel',place.name,place.tags || {},true,false); dialog.showModal(); const images=await commonsImages(place); renderCarousel('detail-carousel',images,place.name,place.tags || {}); }
    async function placeImages(place) { const commons=await commonsImages(place); return place.googlePhoto?.url ? [place.googlePhoto.url,...commons.filter(url=>url!==place.googlePhoto.url)] : commons; }
    async function loadPreview(place) {
      const preview = document.getElementById('preview');
      if (!place) { preview.innerHTML=''; return; }
      previewPlaces.set('selected-preview',place);
      preview.innerHTML=carouselHtml('selected-preview',place.name,place.tags || {},true)+photoNote(place.tags || {},place)+websiteLink(place.tags || {});
      const images=await placeImages(place); renderCarousel('selected-preview',images,place.name,place.tags || {});
    }
    function clearRoute() { [outbound, returnRoute].forEach(layer => { if (layer) map.removeLayer(layer); }); outbound = returnRoute = null; routePoints = []; forwardCoords=[]; returnCoords=[]; weatherRiskLayer.clearLayers(); weatherRouteLayer.clearLayers(); document.getElementById('download').disabled = true; document.getElementById('saveTour').disabled = true; }
    function clearMarkers(items) { items.splice(0).forEach(m => map.removeLayer(m)); }
    function updatePlanningCorridor() { if (planningCorridor) map.removeLayer(planningCorridor); planningCorridor=null; const end=destinationData(); if (!start || !end) return; planningCorridor=L.polyline([[start.lat,start.lng],[end.lat,end.lng]],{color:'#627167',weight:3,opacity:.72,dashArray:'8 9',className:'planning-corridor',interactive:false}).addTo(map); }
    function focusPlanningArea() { const end=destinationData(); if (!start || !end) return; const bounds=L.latLngBounds([[start.lat,start.lng],[end.lat,end.lng]]).pad(.18); map.invalidateSize({pan:false}); map.flyToBounds(bounds,{padding:[36,36],maxZoom:13,duration:.55}); }
    function pointOf(element) { const c = element.center || element; return c ? {lat:c.lat, lng:c.lon} : null; }
    async function overpass(query) {
      const cacheKey=hashText(query), cached=cacheRead('maasduinen-osm-cache',cacheKey,1000*60*30); if (cached) return cached;
      const aborter=new AbortController(), timer=window.setTimeout(()=>aborter.abort(),25000);
      try {
        const response=await fetch('/api/places/overpass',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({query}),signal:aborter.signal});
        const payload=await response.json();
        if (!response.ok) throw new Error(payload.message || 'OpenStreetMap-Suche derzeit nicht erreichbar.');
        const rows=payload.elements || []; cacheWrite('maasduinen-osm-cache',cacheKey,rows,30); return rows;
      } catch (error) {
        if (error.name==='AbortError') throw new Error('Die Zielsuche dauert gerade zu lange. Bitte erneut versuchen.');
        throw new Error(error.message || 'OpenStreetMap-Suche derzeit nicht erreichbar.');
      } finally { window.clearTimeout(timer); }
    }
    function googleTags(type='') { const value=String(type).toLowerCase(); if (value.includes('playground')) return {leisure:'playground'}; if (value.includes('park') || value.includes('tourist_attraction') || value.includes('museum')) return {tourism:'attraction'}; if (value.includes('restaurant')) return {amenity:'restaurant'}; if (value.includes('ice_cream') || value.includes('cafe') || value.includes('coffee')) return {amenity:'cafe'}; if (value.includes('bar')) return {amenity:'biergarten'}; if (value.includes('zoo') || value.includes('animal')) return {tourism:'zoo'}; return {tourism:'attraction'}; }
    async function googleGoals(center,radius) {
      const response=await fetch('/api/places/search',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({text:'Sehenswürdigkeiten',latitude:center.lat,longitude:center.lng,radius})}); const payload=await response.json(); refreshGoogleUsage(); if (!response.ok) throw new Error(payload.message || 'Google Places ist nicht erreichbar.');
      return (payload.places || []).map(place=>({name:place.displayName?.text || place.formattedAddress || 'Ausflugsziel',lat:place.location?.latitude,lng:place.location?.longitude,tags:{...googleTags(place.primaryType),address:place.formattedAddress || '',googlePlaceId:place.id,googleSource:true},distance:place.location?map.distance(center,{lat:place.location.latitude,lng:place.location.longitude}):Infinity})).filter(place=>Number.isFinite(place.lat)&&Number.isFinite(place.lng)&&place.distance<=radius);
    }
    let destinationSearchTimer=null;
    function clearDestinationTextResults() { const list=document.getElementById('destinationSearchResults'); list.innerHTML=''; list.hidden=true; }
    async function searchDestinationText() {
      const text=destinationName.value.trim(), list=document.getElementById('destinationSearchResults');
      if (text.length<3) { clearDestinationTextResults(); return; }
      const center=goalSearchCenter || start;
      if (!center) { status.textContent='Bitte zuerst einen Startpunkt setzen.'; return; }
      list.hidden=false; list.innerHTML='<small class="destination-search-result">Suche nach „'+safe(text)+'“ …</small>';
      try {
        const response=await fetch('/api/places/search',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({text,latitude:center.lat,longitude:center.lng,radius:25000})});
        const payload=await response.json(); refreshGoogleUsage(); if (!response.ok) throw new Error(payload.message || 'Google Places ist nicht erreichbar.');
        const places=(payload.places || []).map(place=>({name:place.displayName?.text || place.formattedAddress || text,lat:place.location?.latitude,lng:place.location?.longitude,tags:{...googleTags(place.primaryType),address:place.formattedAddress || '',googlePlaceId:place.id,googleSource:true},distance:place.location?map.distance(center,{lat:place.location.latitude,lng:place.location.longitude}):Infinity})).filter(place=>Number.isFinite(place.lat)&&Number.isFinite(place.lng)).slice(0,6);
        if (!places.length) { list.innerHTML='<small class="destination-search-result">Keine passenden Orte im Umkreis von 25 km gefunden.</small>'; return; }
        list.innerHTML=places.map((place,index)=>'<button class="destination-search-result" type="button" data-destination-search="'+index+'"><i data-lucide="map-pin" aria-hidden="true"></i><span><strong>'+safe(place.name)+'</strong><small>'+safe(place.tags.address || placeKind(place.tags))+' · '+(place.distance/1000).toFixed(1)+' km entfernt</small></span></button>').join('');
        list.querySelectorAll('[data-destination-search]').forEach(button=>button.addEventListener('click',()=>openGoalCandidateDialog(places[Number(button.dataset.destinationSearch)])));
        setTimeout(()=>window.lucide?.createIcons({attrs:{'aria-hidden':'true'}}),0);
      } catch (error) { list.innerHTML='<small class="destination-search-result">'+safe(error.message || 'Die Ortssuche ist gerade nicht verfügbar.')+'</small>'; }
    }
    async function googleStopFallback(probes,corridor) {
      const selected=[];
      if (document.getElementById('includeCafe').checked) selected.push('Eiscafé','Café');
      if (document.getElementById('includeBeer').checked) selected.push('Biergarten');
      if (document.getElementById('includeRestaurant').checked) selected.push('Restaurant');
      if (document.getElementById('includePlaygrounds').checked) selected.push('Spielplatz');
      if (document.getElementById('includePicnic').checked) selected.push('Picknickplatz');
      if (document.getElementById('includeToilets').checked) selected.push('Öffentliche Toilette');
      if (document.getElementById('includeWater').checked) selected.push('Badestelle');
      if (document.getElementById('includeAnimals').checked) selected.push('Tierpark');
      // Google kann einen Streckenkorridor nicht direkt durchsuchen. Daher verteilen
      // wir maximal acht kleine, überlappende Suchkreise gleichmäßig auf der echten
      // Hinfahrt und filtern jedes Ergebnis danach nochmals auf den 100-m-Proben ab.
      const terms=[...new Set(selected)].slice(0,4), requestBudget=Math.min(8,Math.max(terms.length,terms.length*3)), requests=[];
      terms.forEach((text,termIndex)=>{ const count=Math.floor(requestBudget/terms.length)+(termIndex<requestBudget%terms.length?1:0); for(let index=0;index<count;index+=1) { const ratio=(index+.5)/count, center=probes[Math.min(probes.length-1,Math.round((probes.length-1)*ratio))]; if (center) requests.push({text,center}); } });
      const batches=await Promise.all(requests.map(async ({text,center})=>{ const response=await fetch('/api/places/search',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({text,latitude:center.lat,longitude:center.lng,radius:3000})}); const payload=await response.json(); refreshGoogleUsage(); if (!response.ok) throw new Error(payload.message || 'Google Places ist nicht erreichbar.'); return (payload.places || []).map(place=>({name:place.displayName?.text || place.formattedAddress || text,lat:place.location?.latitude,lng:place.location?.longitude,tags:{...googleTags(place.primaryType),address:place.formattedAddress || '',googlePlaceId:place.id,googleSource:true}})); }));
      const seen=new Set();
      return batches.flat().filter(place=>Number.isFinite(place.lat)&&Number.isFinite(place.lng)&&!seen.has(place.tags.googlePlaceId)&&seen.add(place.tags.googlePlaceId)).map(place=>({...place,routeDistance:strictCorridorDistance(place,probes)})).filter(place=>isWithinStopCorridor(place,probes,corridor)).sort((a,b)=>a.routeDistance-b.routeDistance).slice(0,12);
    }
    async function enrichGooglePlace(place) {
      const id=place.tags?.googlePlaceId; if (!id) return;
      const detailResponse=await fetch('/api/places/details',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({id})}); const detail=await detailResponse.json(); refreshGoogleUsage(); if (!detailResponse.ok) throw new Error(detail.message || 'Ortsdetails konnten nicht geladen werden.');
      place.tags.website=detail.websiteUri || place.tags.website; place.tags.opening_hours=(detail.regularOpeningHours?.weekdayDescriptions || []).join(' · '); place.googleRating=detail.rating; place.googleRatingCount=detail.userRatingCount; const photo=detail.photos?.[0]; if (!photo?.name) return;
      const photoResponse=await fetch('/api/places/photo',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({name:photo.name})}); const media=await photoResponse.json(); refreshGoogleUsage(); if (!photoResponse.ok) throw new Error(media.message || 'Ortsfoto konnte nicht geladen werden.');
      place.googlePhoto={url:media.photoUri,attribution:photo.authorAttributions?.[0]?.displayName || ''};
    }
    async function enrichStopFromGoogle(place) { if (place.tags?.googlePlaceId || place.googleLookupLoading || place.googleLookupDone) return; place.googleLookupLoading=true; try { const response=await fetch('/api/places/search',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({text:place.name,latitude:place.lat,longitude:place.lng,radius:500})}); const payload=await response.json(); refreshGoogleUsage(); if (!response.ok) throw new Error(payload.message || 'Google Places ist nicht erreichbar.'); const match=(payload.places || []).find(candidate=>candidate.location?.latitude && candidate.location?.longitude && map.distance(place,{lat:candidate.location.latitude,lng:candidate.location.longitude})<=250); if (!match) return; place.tags={...place.tags,...googleTags(match.primaryType),address:match.formattedAddress || place.tags.address || '',googlePlaceId:match.id,googleSource:true}; await enrichGooglePlace(place); } finally { place.googleLookupLoading=false; place.googleLookupDone=true; } }
    function nameOf(e) { const t=e.tags||{}; return t.name || t['name:de'] || (t.historic==='castle'||t.historic==='manor'||t.historic==='ruins'?'Schloss / Historische Anlage':t.tourism==='viewpoint'?'Aussichtspunkt':t.leisure==='playground'?'Spielplatz':t.amenity==='biergarten'?'Biergarten':t.amenity==='restaurant'?'Restaurant':t.amenity==='toilets'?'Öffentliche Toilette':t.tourism==='picnic_site'||t.leisure==='picnic_table'||t.amenity==='bbq'?'Picknickplatz':t.leisure==='swimming_area'||t.natural==='beach'||t.natural==='water'?'See / Badestelle':t.tourism==='zoo'||t.animal?'Tiere / Tiergehege':'Sehenswürdigkeit'); }
    function goalPriority(place) { const kind=placeKind(place.tags || {}); return ({'Schloss / Historische Anlage':0,'Ausflugsziel':1,'Aussichtspunkt':2,'Spielplatz':3,'See / Badestelle':4,'Biergarten':5,'Café / Eiscafé':6,'Restaurant':7})[kind] ?? 8; }
    function markFamilyHighlights(rows) { const value={'Schloss / Historische Anlage':10,'Spielplatz':9,'See / Badestelle':8,'Tiere / Tiergehege':8,'Aussichtspunkt':7,'Ausflugsziel':6,'Picknick / Grillplatz':6,'Café / Eiscafé':4,'Biergarten':4,'Restaurant':3}; const used=new Set(); return [...rows].sort((a,b)=>(value[placeKind(b.tags)]||1)*100-(b.distance||0)/40-((value[placeKind(a.tags)]||1)*100-(a.distance||0)/40)).map(place=>({...place,highlightScore:(value[placeKind(place.tags)]||1)*100-(place.distance||0)/40})).sort((a,b)=>b.highlightScore-a.highlightScore).map(place=>{ const kind=placeKind(place.tags); const isHighlight=!place.alternative&&!used.has(kind)&&used.size<5; if (isHighlight) used.add(kind); return {...place,isHighlight}; }); }
    function showGoalResults(rows,radius,alternatives=false,message='') {
      rows=markFamilyHighlights(rows.slice(0,30)); const list=document.getElementById('goalList'); rows.forEach((p,i)=>previewPlaces.set('goal-image-'+i,p)); const highlightCount=rows.filter(p=>p.isHighlight).length;
      list.innerHTML=(alternatives?'<small>Keine Ziele im gewählten Radius. Nächstgelegene Alternativen:</small>':highlightCount?'<small>★ Familien-Highlights: abwechslungsreich, nah und für Kinder besonders interessant.</small>':'')+rows.map((p,i)=>'<div class="place-card'+(p.alternative?' is-alternative':'')+(p.isHighlight?' is-highlight':'')+'">'+carouselHtml('goal-image-'+i,p.name,p.tags)+'<label>'+(p.isHighlight?'<span class="highlight-label">★ Familien-Highlight</span>':'')+'<input type="radio" name="goal" value="'+i+'"><strong>'+safe(p.name)+'</strong><span class="place-kind">'+placeKind(p.tags)+'</span><small>'+Math.round(p.distance/100)/10+' km '+(p.alternative?'außerhalb':'vom Zielbereich')+'</small>'+photoNote(p.tags)+websiteLink(p.tags)+'</label></div>').join('') || '<small>Keine passenden Ziele gefunden.</small>';
      rows.forEach((p,i)=>goalMarkers.push(marker(p.name,p,p.alternative?'#6f7c74':'#246b45',p.alternative?'Alternative':'Vorschlag',goalLayer).on('click',()=>openGoalCandidateDialog(p))));
      list.querySelectorAll('input').forEach((input,i)=>input.addEventListener('change',()=>openGoalCandidateDialog(rows[i]))); hydrateCarousels(rows,'goal-image');
      status.textContent=message || (alternatives?'Keine Ziele im '+(radius/1000)+'-km-Radius; graue Alternativen werden gezeigt.':rows.length+' Zielvorschläge innerhalb von '+(radius/1000)+' km gefunden.');
    }
    async function findGoals() {
      if (!start && !goalSearchCenter) { status.textContent='Bitte zuerst einen Startpunkt setzen.'; return; }
      status.textContent='Zielvorschläge werden aus OpenStreetMap gesucht …'; goalLayer.clearLayers(); goalMarkers=[]; const radius=Number(document.getElementById('radius').value)*1000;
      const center=goalSearchCenter || start; const queryFor=range=>'[out:json][timeout:25];(nwr["tourism"="attraction"](around:'+range+','+center.lat+','+center.lng+');nwr["tourism"="viewpoint"](around:'+range+','+center.lat+','+center.lng+');nwr["historic"~"castle|manor|ruins"](around:'+range+','+center.lat+','+center.lng+');nwr["name"~"Schloss Walbeck|Haus Walbeck",i](around:'+range+','+center.lat+','+center.lng+');nwr["natural"="water"](around:'+range+','+center.lat+','+center.lng+');nwr["leisure"="playground"](around:'+range+','+center.lat+','+center.lng+');nwr["amenity"~"restaurant|biergarten"](around:'+range+','+center.lat+','+center.lng+'););out center 120;';
      try { const googleRows=await googleGoals(center,radius); if (googleRows.length) { googleRows.sort((a,b)=>goalPriority(a)-goalPriority(b)||a.distance-b.distance); showGoalResults(googleRows,radius,false,googleRows.length+' geprüfte Google-Places-Ziele innerhalb von '+(radius/1000)+' km gefunden.'); return; } const toPlaces=raw=>raw.map(e=>({...pointOf(e),name:nameOf(e),tags:e.tags||{}})).filter(p=>p.lat&&p.lng).map(p=>({...p,distance:map.distance(center,p)})); let rows=toPlaces(await overpass(queryFor(radius))).filter(p=>p.distance<=radius); const curated=Object.values(targets).filter(p=>p.tags).map(p=>({...p,distance:map.distance(center,p)})).filter(p=>p.distance<=radius&&!rows.some(row=>map.distance(row,p)<80)); rows.push(...curated); rows.sort((a,b)=>goalPriority(a)-goalPriority(b)||a.distance-b.distance); let alternatives=false; if (!rows.length) { rows=toPlaces(await overpass(queryFor(Math.min(radius*3,30000)))).filter(p=>p.distance>radius).sort((a,b)=>goalPriority(a)-goalPriority(b)||a.distance-b.distance).slice(0,8).map(p=>({...p,alternative:true})); alternatives=true; } showGoalResults(rows,radius,alternatives); } catch(e) {
        const curated=Object.values(targets).filter(p=>p.tags).map(p=>({...p,distance:map.distance(center,p)})).sort((a,b)=>a.distance-b.distance);
        const nearby=curated.filter(p=>p.distance<=radius), fallback=nearby.length?nearby:curated.slice(0,8).map(p=>({...p,alternative:true}));
        showGoalResults(fallback,radius,!nearby.length,'Live-Zielsuche ist gerade nicht erreichbar. Es werden geprüfte lokale Vorschläge angezeigt.');
      }
    }
    function clearGoalSearch() { goalLayer.clearLayers(); goalMarkers=[]; document.getElementById('goalList').innerHTML=''; clearDestinationTextResults(); if (pendingMarker) { map.removeLayer(pendingMarker); pendingMarker=null; } goalOverlay.style.display='none'; radiusOverlay.style.display='none'; radiusCenter=null; goalSearchCenter=null; }
    function selectGoal(p) { clearGoalSearch(); customDestination={name:p.name,lat:p.lat,lng:p.lng,tags:p.tags||{},googlePhoto:p.googlePhoto,googleRating:p.googleRating,googleRatingCount:p.googleRatingCount}; destination.value='custom'; destinationName.value=p.name; updateDestination(); clearRoute(); activatePlannerStep(3); status.textContent='Ziel ausgewählt: '+p.name+'. Wähle nun das Familien-Tourprofil.'; if (p.tags?.googlePlaceId && !p.googleDetailLoading && !p.googlePhoto) { p.googleDetailLoading=true; enrichGooglePlace(p).then(()=>{ if (customDestination?.tags?.googlePlaceId===p.tags.googlePlaceId) { customDestination.googlePhoto=p.googlePhoto; customDestination.googleRating=p.googleRating; customDestination.googleRatingCount=p.googleRatingCount; customDestination.tags=p.tags; updateDestination(true); } }).catch(()=>{}).finally(()=>{ p.googleDetailLoading=false; }); } }
    let activeGoalCandidate=null;
    function openGoalCandidateDialog(place) { activeGoalCandidate=place; const render=()=>{ const tags=place.tags || {}, directKm=start?map.distance(start,place)/1000:0, estimatedKm=directKm*1.18, estimatedMinutes=Math.round(estimatedKm/14*60); document.getElementById('goalCandidateTitle').textContent=place.name; document.getElementById('goalCandidateKind').textContent=placeEmoji(tags)+' '+placeKind(tags)+(place.googleRating?' · ★ '+place.googleRating+' ('+place.googleRatingCount+')':''); document.getElementById('goalCandidateDescription').textContent=previewDescription(place); document.getElementById('goalCandidateTravel').textContent=start?'Ab Start: ca. '+estimatedKm.toFixed(1)+' km · etwa '+estimatedMinutes+' Min. Fahrzeit.':'Bitte zuerst einen Startpunkt setzen.'; document.getElementById('goalCandidateWebsite').innerHTML=websiteLink(tags); const image=document.getElementById('goalCandidateImage'); image.innerHTML=place.googlePhoto?.url?'<figure class="goal-photo"><img src="'+safe(place.googlePhoto.url)+'" alt="'+safe(place.name)+'"><figcaption>'+safe(place.googlePhoto.attribution ? 'Foto: '+place.googlePhoto.attribution : 'Foto: Google Places')+'</figcaption></figure>':''; }; render(); const dialog=document.getElementById('goalCandidateDialog'); if (!dialog.open) dialog.showModal(); if (place.tags?.googlePlaceId && !place.googleDetailLoading && !place.googlePhoto) { place.googleDetailLoading=true; enrichGooglePlace(place).then(render).catch(error=>{ document.getElementById('goalCandidateDescription').textContent='Basisdaten geladen. '+error.message; }).finally(()=>{ place.googleDetailLoading=false; }); } }
    function closeGoalCandidateDialog(confirmed=false) { const candidate=activeGoalCandidate, dialog=document.getElementById('goalCandidateDialog'); if (dialog.open) dialog.close(); activeGoalCandidate=null; if (confirmed && candidate) selectGoal(candidate); }
    function distanceToRouteMeters(point,coords) {
      if (coords.length<2) return Infinity; const latFactor=111320, lngFactor=111320*Math.cos(point.lat*Math.PI/180); let nearest=Infinity;
      for (let i=1;i<coords.length;i++) { const a=coords[i-1],b=coords[i]; const ax=(a.lng-point.lng)*lngFactor, ay=(a.lat-point.lat)*latFactor, bx=(b.lng-point.lng)*lngFactor, by=(b.lat-point.lat)*latFactor; const dx=bx-ax,dy=by-ay,denominator=dx*dx+dy*dy; const t=denominator?Math.max(0,Math.min(1,-(ax*dx+ay*dy)/denominator)):0; nearest=Math.min(nearest,Math.hypot(ax+t*dx,ay+t*dy)); }
      return nearest;
    }
    function strictCorridorDistance(point,probes) { return probes.reduce((nearest,probe)=>Math.min(nearest,map.distance(point,probe)),Infinity); }
    function isWithinStopCorridor(point,probes,corridor) { return strictCorridorDistance(point,probes)<=Math.max(0,corridor-55); }
    function routeSamplesEvery(coords,spacingMeters=500) {
      if (!coords.length) return []; const samples=[coords[0]]; let covered=0,next=spacingMeters;
      for (let i=1;i<coords.length;i++) { const a=coords[i-1],b=coords[i],segment=map.distance(a,b); if (!segment) continue;
        while (covered+segment>=next) { const ratio=(next-covered)/segment; samples.push({lat:a.lat+(b.lat-a.lat)*ratio,lng:a.lng+(b.lng-a.lng)*ratio}); next+=spacingMeters; }
        covered+=segment;
      }
      const end=coords[coords.length-1],last=samples[samples.length-1]; if (last.lat!==end.lat || last.lng!==end.lng) samples.push(end);
      return samples;
    }
    function routeWeatherSegments(coords,spacingMeters=2000) {
      if (coords.length<2) return []; const segments=[]; let covered=0,next=spacingMeters,startKm=0,current=[coords[0]];
      for (let i=1;i<coords.length;i++) { let position=coords[i-1], target=coords[i], remaining=map.distance(position,target); if (!remaining) continue;
        while (remaining>0.05) { const untilNext=next-covered; if (remaining<untilNext) { current.push(target); covered+=remaining; remaining=0; }
          else { const ratio=untilNext/remaining, cut={lat:position.lat+(target.lat-position.lat)*ratio,lng:position.lng+(target.lng-position.lng)*ratio}; current.push(cut); segments.push({coords:current,lat:cut.lat,lng:cut.lng,kmStart:startKm,kmEnd:next/1000}); current=[cut]; startKm=next/1000; covered=next; next+=spacingMeters; position=cut; remaining=map.distance(position,target); }
        }
      }
      if (current.length>1) { const end=coords[coords.length-1]; segments.push({coords:current,lat:end.lat,lng:end.lng,kmStart:startKm,kmEnd:covered/1000}); }
      return segments;
    }
    function weatherTime(seconds) { return new Intl.DateTimeFormat('de-DE',{hour:'2-digit',minute:'2-digit'}).format(new Date(seconds*1000)); }
    function nearestHourIndex(times,seconds) { let best=0,delta=Infinity; times.forEach((time,index)=>{ const current=Math.abs(time-seconds); if (current<delta) { delta=current; best=index; } }); return best; }
    function weatherAlertMarker(sample) { const icon=L.divIcon({className:'weather-marker-wrap',html:'<span class="weather-marker"><i data-lucide="cloud-rain" aria-hidden="true"></i></span>',iconSize:[34,34],iconAnchor:[17,17]}); const marker=L.marker([sample.lat,sample.lng],{icon,keyboard:true,title:'Regenrisiko · '+sample.direction,zIndexOffset:1200}).addTo(weatherRiskLayer).bindPopup('<strong>Regenrisiko · '+sample.direction+'</strong><br>ca. '+weatherTime(sample.arrival)+' · km '+sample.kmStart.toFixed(0)+'–'+sample.kmEnd.toFixed(0)+'<br>Regen '+sample.probability+' % · '+sample.precipitation.toFixed(1)+' mm'); setTimeout(()=>window.lucide?.createIcons({attrs:{'aria-hidden':'true'}}),0); }
    async function checkRouteWeather() {
      if (!forwardCoords.length || !returnCoords.length) { document.getElementById('weatherSummary').textContent='Bitte zuerst eine vollständige Tour berechnen.'; return; }
      const departure=new Date(departureTime.value); if (Number.isNaN(departure.getTime())) { document.getElementById('weatherSummary').textContent='Bitte eine gültige Abfahrtszeit wählen.'; return; }
      const outward=routeWeatherSegments(forwardCoords), homeward=routeWeatherSegments(returnCoords), outKm=outward.at(-1)?.kmEnd || 0, samples=[...outward.map(s=>({...s,direction:'Hinweg',offsetHours:0})),...homeward.map(s=>({...s,direction:'Rückweg',offsetHours:outKm/14}))], latitudes=samples.map(p=>p.lat.toFixed(5)).join(','), longitudes=samples.map(p=>p.lng.toFixed(5)).join(','), summary=document.getElementById('weatherSummary'); summary.textContent='Wetterdaten für Hin- und Rückweg werden geprüft …';
      try { const url='https://api.open-meteo.com/v1/forecast?latitude='+latitudes+'&longitude='+longitudes+'&hourly=precipitation,precipitation_probability,wind_speed_10m,weather_code&forecast_days=3&timeformat=unixtime&timezone=GMT'; const raw=await (await fetch(url)).json(), forecasts=Array.isArray(raw)?raw:[raw], rainRisks=[], windRisks=[], rainWasActive={Hinweg:false,Rückweg:false}; weatherRiskLayer.clearLayers(); weatherRouteLayer.clearLayers();
        samples.forEach((sample,index)=>{ const hourly=forecasts[index]?.hourly; if (!hourly) return; const arrival=Math.round((departure.getTime()/1000)+(sample.offsetHours+sample.kmEnd/14)*3600), hour=nearestHourIndex(hourly.time,arrival), precipitation=Number(hourly.precipitation?.[hour] || 0), probability=Number(hourly.precipitation_probability?.[hour] || 0), wind=Number(hourly.wind_speed_10m?.[hour] || 0), rain=precipitation>=0.1 || probability>=35; if (rain) { const item={...sample,arrival,precipitation,probability,wind}; rainRisks.push(item); L.polyline(sample.coords,{color:'#dc2626',weight:11,opacity:.92,className:'weather-rain-segment'}).addTo(weatherRouteLayer).bindPopup('<strong>Regenrisiko · '+sample.direction+'</strong><br>ca. '+weatherTime(arrival)+' · km '+sample.kmStart.toFixed(0)+'–'+sample.kmEnd.toFixed(0)+'<br>Regen '+probability+' % · '+precipitation.toFixed(1)+' mm'); if (!rainWasActive[sample.direction]) weatherAlertMarker(item); rainWasActive[sample.direction]=true; } else rainWasActive[sample.direction]=false;
          if (wind>=35) windRisks.push({...sample,arrival,wind});
        });
        if (!rainRisks.length && !windRisks.length) { summary.innerHTML='<strong class="clear-weather">✓ Kein auffälliges Regen- oder Windrisiko auf Hin- und Rückweg.</strong><small>Prognose für die gewählte Abfahrtszeit; Wetter kann sich ändern.</small>'; return; }
        const notes=[]; ['Hinweg','Rückweg'].forEach(direction=>{ const affected=rainRisks.filter(r=>r.direction===direction); if (affected.length) { const first=affected[0],last=affected.at(-1); notes.push(direction+': Regenrisiko ca. '+weatherTime(first.arrival)+' bei km '+first.kmStart.toFixed(0)+'–'+last.kmEnd.toFixed(0)+'.'); } }); if (windRisks.length) notes.push('Kräftiger Wind an '+windRisks.length+' Streckenabschnitt(en).'); summary.innerHTML='<strong class="rain-risk">⚠ Rote, animierte Route = Regenrisiko</strong><ul>'+notes.map(note=>'<li>'+note+'</li>').join('')+'</ul><small>Jeder Abschnitt entspricht etwa 2 km Fahrstrecke; anklicken zeigt Details.</small>';
      } catch (error) { summary.textContent='Wetterdaten konnten gerade nicht geladen werden: '+error.message; }
    }
    function reconcileStopCandidates() {
      if (!forwardCoords.length || !stopCandidateState.length) return; const corridor=stopCorridorMeters(), probes=routeSamplesEvery(forwardCoords,100);
      stopCandidateState.forEach(entry=>{ const distance=strictCorridorDistance(entry.place,probes); entry.place.routeDistance=distance; entry.pin.setTooltipContent(stopTooltip(entry.place)); const keep=isWithinStopCorridor(entry.place,probes,corridor); const card=entry.input.closest('.place-card'); if (card) card.hidden=!keep; if (!keep) entry.input.checked=false; if (keep && !stopLayer.hasLayer(entry.pin)) stopLayer.addLayer(entry.pin); if (!keep && stopLayer.hasLayer(entry.pin)) stopLayer.removeLayer(entry.pin); });
      selectedStops=stopCandidateState.filter(entry=>entry.input.checked).map(entry=>entry.place);
    }
    function setStopSearchLoading(active) { const button=document.getElementById('findStops'), label=button.querySelector('span:last-child'); button.disabled=active; button.classList.toggle('is-loading',active); button.setAttribute('aria-busy',String(active)); if (label) label.textContent=active?'Suche läuft …':'Stopps suchen'; if (active) { const version=stopSearchVersion; window.setTimeout(function finishWhenRendered(){ const searching=status.textContent.includes('Stopps werden'), loadingCard=document.querySelector('#stopList .stop-search-progress'); if (version!==stopSearchVersion || !searching || !loadingCard) { setStopSearchLoading(false); return; } window.setTimeout(finishWhenRendered,180); },180); } }
    function groupStopCards(rows,list) { if (!rows.length) return; const cards=[...list.querySelectorAll('.place-card')], groups=new Map(); rows.forEach((place,index)=>{ const kind=placeKind(place.tags); if (!groups.has(kind)) groups.set(kind,[]); groups.get(kind).push({place,index,card:cards[index]}); }); const firstKind=groups.keys().next().value; list.innerHTML=''; groups.forEach((entries,kind)=>{ const group=document.createElement('details'); group.className='stop-group'; group.open=kind===firstKind; const summary=document.createElement('summary'); summary.innerHTML='<i data-lucide="'+({'Café / Eiscafé':'coffee','Biergarten':'beer','Restaurant':'utensils','Sehenswürdigkeit':'binoculars','Spielplatz':'toy-brick','Picknick / Grillplatz':'sandwich','Öffentliche Toilette':'toilet','See / Badestelle':'waves','Tiere / Tiergehege':'rabbit'}[kind] || 'map-pin')+'" aria-hidden="true"></i><span>'+safe(kind)+'</span><b class="stop-group-count">'+entries.length+'</b>'; const content=document.createElement('div'); content.className='stop-group-list'; entries.forEach(entry=>content.append(entry.card)); group.append(summary,content); list.append(group); }); setTimeout(()=>window.lucide?.createIcons({attrs:{'aria-hidden':'true'}}),0); }
    async function findStops() {
      if (!start || !destinationData()) { status.textContent='Bitte zuerst Start und Ziel auswählen.'; return; }
      if (!forwardCoords.length) { await calculate(); if (!forwardCoords.length) return; }
      const corridor=stopCorridorMeters(); setStopSearchLoading(true); status.textContent='Stopps werden alle 500 m entlang der Hinfahrt im '+corridor+'-m-Korridor gesucht …'; clearStopMarkers(); stopCandidateState=[]; selectedStops=[]; document.getElementById('stopList').innerHTML='<div class="stop-search-progress">Suche entlang der echten Hinfahrt – maximal '+corridor+' m neben der Route.</div>'; const sample=routeSamplesEvery(forwardCoords,500), strictProbes=routeSamplesEvery(forwardCoords,100); const categories=[]; if (document.getElementById('includeCafe').checked) categories.push('nwr["amenity"~"cafe|ice_cream"]'); if (document.getElementById('includeBeer').checked) categories.push('nwr["amenity"="biergarten"]'); if (document.getElementById('includeRestaurant').checked) categories.push('nwr["amenity"="restaurant"]'); if (document.getElementById('includeSights').checked) categories.push('nwr["tourism"="viewpoint"]'); if (document.getElementById('includePlaygrounds').checked) categories.push('nwr["leisure"="playground"]'); if (document.getElementById('includePicnic').checked) categories.push('nwr["tourism"="picnic_site"]','nwr["leisure"="picnic_table"]','nwr["amenity"="bbq"]'); if (document.getElementById('includeToilets').checked) categories.push('nwr["amenity"="toilets"]'); if (document.getElementById('includeWater').checked) categories.push('nwr["leisure"="swimming_area"]','nwr["natural"="beach"]','nwr["natural"="water"]["access"!="private"]'); if (document.getElementById('includeAnimals').checked) categories.push('nwr["tourism"="zoo"]','nwr["animal"]');
      try { if (!categories.length) { status.textContent='Bitte mindestens eine Stopp-Kategorie auswählen.'; return; } const coordinates=sample.map(p=>p.lat+','+p.lng).join(','), around='around:'+corridor+','+coordinates, clauses=categories.map(category=>category+'('+around+');').join(''); const raw=await overpass('[out:json][timeout:30];('+clauses+');out center 300;'); const seen=new Set(), rows=raw.map(e=>({...pointOf(e),name:nameOf(e),tags:e.tags||{}})).filter(p=>p.lat&&p.lng&&!seen.has(p.lat.toFixed(4)+p.lng.toFixed(4))&&seen.add(p.lat.toFixed(4)+p.lng.toFixed(4))).map(p=>({...p,routeDistance:strictCorridorDistance(p,strictProbes)})).filter(p=>isWithinStopCorridor(p,strictProbes,corridor)).sort((a,b)=>a.routeDistance-b.routeDistance).slice(0,25); const list=document.getElementById('stopList'); rows.forEach((p,i)=>previewPlaces.set('stop-image-'+i,p)); list.innerHTML=rows.map((p,i)=>'<div class="place-card">'+carouselHtml('stop-image-'+i,p.name,p.tags)+'<label><input type="checkbox" value="'+i+'"><strong>'+safe(p.name)+'</strong><span class="place-kind">'+placeKind(p.tags)+'</span><small>'+Math.round(p.routeDistance)+' m neben der Hinfahrt</small>'+photoNote(p.tags)+websiteLink(p.tags)+'</label></div>').join('') || '<small>Keine passenden Stopps innerhalb von '+corridor+' m neben der Hinfahrt gefunden.</small>'; const inputs=[...list.querySelectorAll('input')]; const version=++stopSearchVersion; rows.forEach((p,i)=>{ const pin=stopMarker(p,i,inputs[i],version); stopMarkers.push(pin); stopCandidateState.push({place:p,pin,input:inputs[i]}); }); inputs.forEach(input=>input.addEventListener('change',()=>{ selectedStops=inputs.filter(x=>x.checked).map(x=>rows[Number(x.value)]); status.textContent=selectedStops.length+' Stopp(s) gewählt – Hinfahrt wird neu berechnet …'; calculate(); })); hydrateCarousels(rows,'stop-image'); status.textContent=rows.length+' optionale Stopps innerhalb von '+corridor+' m sind auf der Karte sichtbar. Klicke ein Symbol für Details und bestätige dann den Stopp.'; } catch(e) { try { const rows=await googleStopFallback(strictProbes,corridor); const list=document.getElementById('stopList'); rows.forEach((p,i)=>previewPlaces.set('stop-image-'+i,p)); list.innerHTML=rows.map((p,i)=>'<div class="place-card">'+carouselHtml('stop-image-'+i,p.name,p.tags)+'<label><input type="checkbox" value="'+i+'"><strong>'+safe(p.name)+'</strong><span class="place-kind">'+placeKind(p.tags)+'</span><small>'+Math.round(p.routeDistance)+' m neben der Hinfahrt</small>'+photoNote(p.tags)+websiteLink(p.tags)+'</label></div>').join('') || '<small>Keine passenden Stopps innerhalb von '+corridor+' m neben der Hinfahrt gefunden.</small>'; const inputs=[...list.querySelectorAll('input')]; const version=++stopSearchVersion; rows.forEach((p,i)=>{ const pin=stopMarker(p,i,inputs[i],version); stopMarkers.push(pin); stopCandidateState.push({place:p,pin,input:inputs[i]}); }); inputs.forEach(input=>input.addEventListener('change',()=>{ selectedStops=inputs.filter(x=>x.checked).map(x=>rows[Number(x.value)]); status.textContent=selectedStops.length+' Stopp(s) gewählt – Hinfahrt wird neu berechnet …'; calculate(); })); hydrateCarousels(rows,'stop-image'); status.textContent=rows.length?'OpenStreetMap ist derzeit nicht erreichbar – '+rows.length+' passende Google-Places-Stopps im '+corridor+'-m-Korridor sind sichtbar.':'Keine passenden Google-Places-Stopps innerhalb von '+corridor+' m gefunden.'; } catch (fallbackError) { status.textContent=e.message+' Google-Ausweichsuche: '+fallbackError.message; } }
    }
    function emphasizedRoute(geojson, direction) {
      const isOutbound=direction==='outbound';
      const outline=L.geoJSON(geojson,{style:{color:'#fff',weight:isOutbound?13:0,opacity:isOutbound ? 0.92 : 0,className:isOutbound?'route-outline route-outline-outbound':'route-outline route-outline-return',interactive:false}});
      const line=L.geoJSON(geojson,{style:{color:isOutbound?'#087fc4':'#e05d14',weight:isOutbound?7:4,opacity:1,dashArray:isOutbound?undefined:'10 8',className:isOutbound?'route-outbound':'route-return'}});
      const flow=isOutbound?L.geoJSON(geojson,{style:{color:'#e0f5ff',weight:2.5,opacity:1,dashArray:'9 22',className:'route-outbound-flow',interactive:false}}):null;
      return L.layerGroup(flow?[outline,line,flow]:[outline,line]).addTo(map);
    }
    function showDraft(reason='Der Routendienst war gerade nicht erreichbar.') {
      // Eine Luftlinie ist für eine Fahrradtour irreführend. Ohne echte
      // Routengeometrie zeigen wir deshalb bewusst keine exportierbare Tour an.
      routePoints=[]; forwardCoords=[]; returnCoords=[];
      document.getElementById('distance').textContent='—'; document.getElementById('duration').textContent='—';
      document.getElementById('download').disabled=true; document.getElementById('saveTour').disabled=true;
      status.textContent='Keine Straßenroute angezeigt. '+reason+' Bitte die Berechnung erneut starten.';
    }
    const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
    async function routeRequest(coordinates, preference='recommended', alternativeRoutes=false, attempt=0) {
      try {
        const response = await fetch(routeEndpoint, { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'}, body:JSON.stringify({ coordinates, preference, alternative_routes:alternativeRoutes, avoid_features: document.getElementById('avoidBusy').checked ? ['steps','ferries','fords'] : [] }) });
        if ((response.status === 429 || response.status >= 500) && attempt < 1) { await pause(900); return routeRequest(coordinates, preference, alternativeRoutes, attempt + 1); }
        const payload=await response.json().catch(()=>null);
        if (!response.ok) throw new Error(payload?.message || ('Routendienst antwortet mit '+response.status));
        return payload;
      } catch (error) {
        if (attempt < 1 && !(error.message || '').includes('antwortet mit 4')) { await pause(900); return routeRequest(coordinates, preference, alternativeRoutes, attempt + 1); }
        throw error;
      }
    }
    function returnDetourCandidates(coords) { if (coords.length<8) return []; const index=Math.max(3,Math.min(coords.length-4,Math.round(coords.length*.48))), before=coords[Math.max(0,index-5)], after=coords[Math.min(coords.length-1,index+5)], center=coords[index], longitudeScale=111320*Math.cos(center.lat*Math.PI/180), east=(after.lng-before.lng)*longitudeScale, north=(after.lat-before.lat)*111320, length=Math.hypot(east,north); if (!length) return []; const offset=1400, normalEast=-north/length*offset, normalNorth=east/length*offset; return [1,-1].map(sign=>({lat:center.lat+sign*normalNorth/111320,lng:center.lng+sign*normalEast/longitudeScale})); }
    function routeOverlapRatio(candidate,base) { const stride=Math.max(1,Math.floor(candidate.length/45)), sample=candidate.filter((_,index)=>index%stride===0); return sample.length ? sample.filter(point=>distanceToRouteMeters(point,base)<=180).length/sample.length : 1; }
    async function selectReturnRoute(backData,end) { const base=backData.features[0], supplied=backData.features.slice(1); if (supplied.length) return {feature:[...supplied].sort((a,b)=>a.properties.summary.distance-b.properties.summary.distance)[0],mode:'service'}; const baseCoords=base.geometry.coordinates.map(point=>({lat:point[1],lng:point[0]})), candidates=returnDetourCandidates(baseCoords); if (!candidates.length) return {feature:base,mode:'fallback'}; const results=await Promise.all(candidates.map(async via=>{ try { const data=await routeRequest([[end.lng,end.lat],[via.lng,via.lat],[start.lng,start.lat]],'recommended'); return data.features?.[0] || null; } catch { return null; } })); const acceptable=results.filter(Boolean).map(feature=>{ const coords=feature.geometry.coordinates.map(point=>({lat:point[1],lng:point[0]})); return {feature,overlap:routeOverlapRatio(coords,baseCoords),distance:feature.properties.summary.distance}; }).filter(item=>item.distance<=base.properties.summary.distance*1.45 && item.overlap<.88).sort((a,b)=>a.overlap-b.overlap || a.distance-b.distance); return acceptable.length ? {feature:acceptable[0].feature,mode:'generated'} : {feature:base,mode:'fallback'}; }
    async function calculate() {
      if (!start) { status.textContent = 'Bitte zuerst den Startpunkt auf der Karte setzen.'; return; }
      const calculationId=++routeCalculationVersion; clearRoute(); const end = destinationData();
      if (!end) { status.textContent = 'Bitte zuerst ein Ziel wählen oder auf der Karte setzen.'; return; }
      status.textContent = 'Route wird berechnet …';
      try {
        const outPoints = [[start.lng,start.lat], ...selectedStops.map(p=>[p.lng,p.lat]), [end.lng,end.lat]];
        const returnMode=document.getElementById('returnMode').value, directReturn=returnMode==='direct';
        const backPoints = [[end.lng,end.lat],[start.lng,start.lat]];
        const [outData, backData] = await Promise.all([routeRequest(outPoints,'recommended'), routeRequest(backPoints,directReturn?'fastest':'recommended',!directReturn)]);
        if (calculationId!==routeCalculationVersion) return;
        const returnChoice=directReturn ? {feature:backData.features[0],mode:'direct'} : await selectReturnRoute(backData,end);
        if (calculationId!==routeCalculationVersion) return;
        const alternativeFeature=returnChoice.feature;
        const outCoords = outData.features[0].geometry.coordinates.map(p => ({lat:p[1],lng:p[0]})); const backCoords = alternativeFeature.geometry.coordinates.map(p => ({lat:p[1],lng:p[0]}));
        const outSummary = outData.features[0].properties.summary, backSummary = alternativeFeature.properties.summary;
        forwardCoords = outCoords; returnCoords = backCoords; routePoints = outCoords.concat(backCoords.slice(1)); outbound = emphasizedRoute({type:'FeatureCollection',features:[outData.features[0]]},'outbound');
        returnRoute = emphasizedRoute({type:'FeatureCollection',features:[alternativeFeature]},'return');
        // Die Koordinaten selbst verwenden: verschachtelte Layer-Gruppen können in Leaflet
        // beim Ermitteln der Bounds fehlschlagen und würden die ansonsten fertige Route ausblenden.
        map.fitBounds(L.latLngBounds(outCoords.concat(backCoords)).pad(.15)); const totalKm = (outSummary.distance + backSummary.distance) / 1000; const totalMinutes = (outSummary.duration + backSummary.duration) / 60;
        reconcileStopCandidates();
        document.getElementById('distance').textContent = totalKm.toFixed(1) + ' km gesamt'; document.getElementById('duration').textContent = Math.round(totalMinutes) + ' Min. ohne Pausen'; document.getElementById('download').disabled = false; document.getElementById('saveTour').disabled = false;
        const returnInfo=document.getElementById('returnRouteInfo'); const returnLabel=returnChoice.mode==='direct'?'Direkter, schneller Rückweg.':returnChoice.mode==='service'?'Abweichender Rückweg von OpenRouteService gefunden.':returnChoice.mode==='generated'?'Abweichender Rückweg über eine zusätzliche, straßengebundene Variante gefunden.':'Keine sinnvolle abweichende Rückroute verfügbar – die schnellste Rückroute wird angezeigt.'; returnInfo.textContent=returnLabel; returnInfo.classList.toggle('rain-risk',returnChoice.mode==='fallback');
        status.textContent = 'Hinweg: ruhige Fahrradroute. Rückweg: '+(returnChoice.mode==='fallback'?'keine geeignete Alternative verfügbar':directReturn?'direkt und schnell':'abweichende Route')+'. Bitte die vorgeschlagenen Wege vor der Fahrt auf Anhänger-Tauglichkeit prüfen.'; checkRouteWeather();
      } catch (error) { if (calculationId===routeCalculationVersion) showDraft('Route mit OpenRouteService nicht verfügbar (' + error.message + ').'); }
    }
    function downloadGpx() {
      if (!routePoints.length) return; const name = destinationData().name;
      const xml=value=>String(value).replace(/[<>&"']/g,char=>({'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;',"'":'&apos;'}[char])); const point=(tag,p,label,symbol='Waypoint')=>'<'+tag+' lat="'+p.lat.toFixed(6)+'" lon="'+p.lng.toFixed(6)+'"><name>'+xml(label)+'</name><sym>'+symbol+'</sym></'+tag+'>';
      const waypoints=[point('wpt',start,'Startpunkt','Flag, Blue'),...selectedStops.map(stop=>point('wpt',stop,stop.name,'Restroom')),point('wpt',destinationData(),'Ziel: '+name,'Flag, Green')].join('');
      const points = routePoints.map((p,i) => '<trkpt lat="'+p.lat.toFixed(6)+'" lon="'+p.lng.toFixed(6)+'"><name>'+xml(i === 0 || i === routePoints.length-1 ? 'Startpunkt' : i === forwardCoords.length-1 ? 'Ziel' : 'Routenpunkt')+'</name></trkpt>').join('');
      const tourName='Geldern – '+name, gpx = '<?xml version="1.0" encoding="UTF-8"?><gpx version="1.1" creator="Familien-Radtourenplaner" xmlns="http://www.topografix.com/GPX/1/1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd"><metadata><name>'+xml(tourName)+'</name><time>'+new Date().toISOString()+'</time></metadata>'+waypoints+'<trk><name>'+xml(tourName)+'</name><type>cycling</type><trkseg>'+points+'</trkseg></trk></gpx>';
      const link = document.createElement('a'); link.href=URL.createObjectURL(new Blob([gpx],{type:'application/gpx+xml'})); link.download='geldern-'+destination.value+'.gpx'; link.click(); URL.revokeObjectURL(link.href);
    }
    function renderSavedTours() {
      const list=document.getElementById('savedTours'), tours=savedTours();
      list.innerHTML=tours.length ? '<small>Gespeicherte Touren auf diesem Gerät</small>'+tours.map((tour,i)=>'<div class="saved-tour"><span><strong>'+safe(tour.name)+'</strong><br><small>'+safe(tour.date)+' · '+safe(tour.distance || 'Route')+'</small></span><span><button class="secondary" data-load-tour="'+i+'" type="button">Laden</button><button class="secondary" data-delete-tour="'+i+'" type="button" aria-label="Tour löschen">×</button></span></div>').join('') : '';
      list.querySelectorAll('[data-load-tour]').forEach(button=>button.addEventListener('click',()=>loadTour(Number(button.dataset.loadTour))));
      list.querySelectorAll('[data-delete-tour]').forEach(button=>button.addEventListener('click',()=>{ const tours=savedTours(); tours.splice(Number(button.dataset.deleteTour),1); localStorage.setItem('maasduinen-saved-tours',JSON.stringify(tours)); renderSavedTours(); }));
    }
    function saveTour() {
      const end=destinationData(); if (!start || !end || !routePoints.length) return;
      const tours=savedTours(); tours.unshift({ name:end.name, date:new Date().toLocaleDateString('de-DE'), distance:document.getElementById('distance').textContent, start, end, stops:selectedStops, returnMode:document.getElementById('returnMode').value, travelSetup:selectedTravelProfile() });
      localStorage.setItem('maasduinen-saved-tours',JSON.stringify(tours.slice(0,20))); renderSavedTours(); status.textContent='Tour wurde auf diesem Gerät gespeichert.';
    }
    function loadTour(index) {
      const tour=savedTours()[index]; if (!tour) return; start=tour.start; customDestination={...tour.end}; destination.value='custom'; destinationName.value=tour.end.name; selectedStops=Array.isArray(tour.stops)?tour.stops:[]; document.getElementById('returnMode').value=tour.returnMode || 'alternative'; const setup=document.querySelector('input[name="travelSetup"][value="'+(tour.travelSetup || 'seat')+'"]'); if (setup) { setup.checked=true; renderTravelSetup(); }
      if (startMarker) map.removeLayer(startMarker); startMarker=marker('Startpunkt',start,'#1769aa','Start'); clearStopMarkers(); selectedStops.forEach(place=>stopMarkers.push(confirmedStopMarker(place))); updateDestination(true); calculate(); status.textContent='Gespeicherte Tour wird geladen und neu berechnet …';
    }
    document.querySelectorAll('input[name="travelSetup"]').forEach(input=>input.addEventListener('change',()=>{ renderTravelSetup(); status.textContent=travelProfiles[selectedTravelProfile()].label+' gewählt. Die Routenempfehlung bleibt auf ruhige, feste Wege ausgerichtet.'; }));
    document.getElementById('useGps').addEventListener('click', () => { if (!navigator.geolocation) { status.textContent='GPS wird von diesem Browser nicht unterstützt.'; return; } if (!window.isSecureContext) { status.textContent='GPS benötigt HTTPS oder localhost. Öffne den Planer über https:// oder lokal über localhost/127.0.0.1.'; return; } status.textContent='Bitte GPS-Freigabe im Browser bestätigen …'; navigator.geolocation.getCurrentPosition(p=>{ setStart({lat:p.coords.latitude,lng:p.coords.longitude}); status.textContent='GPS-Startpunkt gesetzt (Genauigkeit ca. '+Math.round(p.coords.accuracy)+' m).'; },error=>{ const messages={1:'GPS-Freigabe wurde im Browser abgelehnt.',2:'Die GPS-Position ist derzeit nicht verfügbar.',3:'Die GPS-Abfrage hat zu lange gedauert.'}; status.textContent=messages[error.code] || 'GPS-Position konnte nicht gelesen werden.'; },{enableHighAccuracy:true,timeout:15000,maximumAge:30000}); });
    async function findStartAddress() { const q=document.getElementById('startAddress').value.trim(); if (!q) return; status.textContent='Adresse wird gesucht …'; try { const r=await fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q='+encodeURIComponent(q)); const d=await r.json(); if (!d[0]) throw new Error('Adresse nicht gefunden.'); setStart({lat:Number(d[0].lat),lng:Number(d[0].lon)}); } catch(e) { status.textContent=e.message; } }
    document.getElementById('findAddress').addEventListener('click',findStartAddress);
    document.getElementById('setDestination').addEventListener('click', () => { mapMode='goal'; status.textContent = 'Klicke auf die Karte, um einen Zielbereich für Vorschläge festzulegen.'; });
    document.getElementById('saveFavorite').addEventListener('click', () => { const place = destinationData(); if (!place) { status.textContent='Bitte zuerst ein Ziel wählen.'; return; } const id = 'favorite-' + Date.now(); const saved=favorites(); saved[id] = {...place, name:(destinationName.value.trim() || place.name), wiki:place.wiki || ''}; localStorage.setItem('maasduinen-favorites', JSON.stringify(saved)); renderDestinations(id); status.textContent='Favorit wurde nur auf diesem Gerät gespeichert.'; });
    destination.addEventListener('change', () => { clearGoalSearch(); updateDestination(); clearRoute(); if (destinationData()) activatePlannerStep(3); }); document.getElementById('radius').addEventListener('input',e=>document.getElementById('radiusValue').textContent=e.target.value+' km'); document.getElementById('findGoals').addEventListener('click',()=>{ activatePlannerStep(2); findGoals(); }); document.getElementById('findDestinationText').addEventListener('click',searchDestinationText); destinationName.addEventListener('input',()=>{ window.clearTimeout(destinationSearchTimer); destinationSearchTimer=window.setTimeout(searchDestinationText,500); }); destinationName.addEventListener('keydown',event=>{ if (event.key==='Enter') { event.preventDefault(); searchDestinationText(); } }); document.getElementById('findStops').addEventListener('click',findStops); document.getElementById('continueToRoute').addEventListener('click',()=>activatePlannerStep(4)); document.getElementById('calculate').addEventListener('click',async()=>{ await calculate(); if (routePoints.length) activatePlannerStep(4); }); document.getElementById('saveTour').addEventListener('click',saveTour); document.getElementById('download').addEventListener('click', downloadGpx);
    departureTime.addEventListener('change',()=>{ if (forwardCoords.length && returnCoords.length) checkRouteWeather(); });
    document.addEventListener('click', event => { if (!(event.target instanceof Element)) return; const opener=event.target.closest('[data-open-preview]'); if (opener) openPreview(opener.dataset.openPreview); }); document.getElementById('closePreview').addEventListener('click',()=>document.getElementById('previewDialog').close());
    document.getElementById('confirmStopDialog').addEventListener('click',()=>closeStopDialog(true)); document.getElementById('cancelStopDialog').addEventListener('click',()=>closeStopDialog(false)); document.getElementById('closeStopDialog').addEventListener('click',()=>closeStopDialog(false)); document.getElementById('stopDialog').addEventListener('cancel',()=>{ activeStopCandidate=null; });
    document.getElementById('confirmGoalCandidate').addEventListener('click',()=>closeGoalCandidateDialog(true)); document.getElementById('cancelGoalCandidate').addEventListener('click',()=>closeGoalCandidateDialog(false)); document.getElementById('closeGoalCandidateDialog').addEventListener('click',()=>closeGoalCandidateDialog(false)); document.getElementById('goalCandidateDialog').addEventListener('cancel',()=>{ activeGoalCandidate=null; });
    const goalDialog=document.getElementById('goalDialog'); let pendingGoal=null;
    map.on('click', e => { if (!start || mapMode==='start') { setStart(e.latlng); mapMode=null; return; } mapMode=null; pendingGoal=e.latlng; showGoalOverlay(e.latlng,'Zielbereich'); goalDialog.showModal(); });
    document.getElementById('confirmGoalPoint').addEventListener('click', () => { if (!pendingGoal) return; goalSearchCenter={lat:pendingGoal.lat,lng:pendingGoal.lng}; customDestination=null; destination.value=''; if (destinationMarker) { map.removeLayer(destinationMarker); destinationMarker=null; } updatePlanningCorridor(); clearRoute(); clearStopSuggestions(); const km=Number(document.getElementById('dialogRadius').value); document.getElementById('radius').value=km; document.getElementById('radiusValue').textContent=km+' km'; showGoalOverlay(pendingGoal,'Zielbereich · '+km+' km'); showRadiusOverlay(pendingGoal,km*1000); activatePlannerStep(2); status.textContent='Zielbereich markiert. Vorschläge werden gesucht …'; setTimeout(findGoals,0); });
    document.getElementById('useGoalPoint').addEventListener('click', () => { if (!pendingGoal) return; goalDialog.close(); selectGoal({name:'Markierter Zielpunkt',lat:pendingGoal.lat,lng:pendingGoal.lng,tags:{}}); });
    new ResizeObserver(() => map.invalidateSize({pan:false})).observe(document.getElementById('map'));
    setTimeout(() => map.invalidateSize({pan:false}), 400);
    const savedTravelSetup=localStorage.getItem('maasduinen-travel-setup'); const savedTravelInput=document.querySelector('input[name="travelSetup"][value="'+savedTravelSetup+'"]'); if (savedTravelInput) savedTravelInput.checked=true; renderTravelSetup();
    setupPlannerSteps(); renderDestinations(); updateDestination(); renderSavedTours();
    refreshGoogleUsage(); setInterval(refreshGoogleUsage,30000);
    setTimeout(() => window.lucide?.createIcons({attrs:{'aria-hidden':'true'}}), 0);
    // Die voreingestellte Heimatadresse wird direkt als sichtbarer Startpunkt gesetzt.
    // Die vorausgefüllte Adresse ist nur eine Starthilfe. Setzt jemand vorher
    // bewusst einen Punkt auf der Karte oder nutzt den aktuellen Standort,
    // darf die verzögerte Adresssuche diese Auswahl nicht wieder überschreiben.
    setTimeout(() => { if (!start) findStartAddress(); },650);
  </script>
</body>
</html>
