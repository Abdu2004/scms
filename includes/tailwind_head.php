    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <style type="text/tailwindcss">
      @theme {
        --color-brand-primary:   #1E3A8A;
        --color-brand-secondary: #0EA5E9;
        --color-brand-success:   #059669;
        --color-brand-warning:   #D97706;
        --color-brand-danger:    #DC2626;
        --font-sans:    'Inter', sans-serif;
        --font-heading: 'Poppins', sans-serif;
      }

      @layer base {
        body  { @apply font-sans bg-slate-50 text-slate-800; }
        h1    { @apply font-heading text-2xl font-semibold text-slate-900 mb-4; }
        h2    { @apply font-heading text-lg font-semibold text-slate-900 mt-8 mb-3; }
        label { @apply block mt-4 text-sm font-medium text-slate-700; }
        input, select, textarea {
          @apply w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white
                 focus:outline-none focus:ring-2 focus:ring-brand-secondary focus:border-brand-secondary;
        }
      }

      @layer components {
        /* Top navigation */
        .topbar        { @apply flex justify-between items-center bg-brand-primary text-white px-6 py-3 flex-wrap gap-3 sticky top-0 z-10 shadow-md; }
        .topbar-brand  { @apply font-heading font-semibold text-sm sm:text-base; }
        .topbar-nav    { @apply flex items-center gap-4 flex-wrap text-sm; }
        .topbar-nav a  { @apply text-blue-100 hover:text-white transition-colors no-underline; }
        .topbar-user   { @apply text-blue-200 text-xs; }
        .notif-link    { @apply relative; }
        .badge         { @apply bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full ml-1; }
        .btn-link      { @apply text-blue-700 hover:text-blue-900 bg-transparent border-0 cursor-pointer text-sm p-0 no-underline; }
        .topbar .btn-link { @apply text-blue-100 hover:text-white; }

        /* Layout */
        .page-container { @apply max-w-5xl mx-auto px-6 py-8; }
        .page-footer     { @apply text-center text-slate-400 text-xs py-8; }

        /* Auth / landing */
        .auth-container  { @apply max-w-md mx-auto mt-16 bg-white p-8 rounded-2xl shadow-lg; }
        .landing         { @apply max-w-xl mx-auto mt-24 text-center; }
        .landing-actions { @apply flex gap-4 justify-center mt-6; }

        /* Forms */
        .form-card { @apply bg-white p-6 rounded-2xl shadow-md mb-6; }
        .two-col   { @apply grid grid-cols-1 md:grid-cols-2 gap-6 items-start; }

        /* Buttons */
        .btn         { @apply inline-block px-4 py-2 rounded-lg bg-slate-200 text-slate-800 text-sm mt-4 cursor-pointer hover:bg-slate-300 transition-colors no-underline; }
        .btn-primary { @apply bg-brand-primary text-white hover:bg-blue-900; }
        .btn-danger  { @apply text-brand-danger; }

        /* Stats */
        .stats-grid  { @apply grid grid-cols-2 sm:grid-cols-5 gap-4 my-6; }
        .stat-card   { @apply bg-white rounded-2xl p-5 text-center shadow-md; }
        .stat-number { @apply block text-3xl font-heading font-bold text-brand-primary; }
        .stat-label  { @apply text-slate-500 text-xs uppercase tracking-wide; }

        /* Tables */
        .section-header { @apply flex justify-between items-center mt-8 flex-wrap gap-3; }
        .data-table       { @apply w-full bg-white rounded-2xl shadow-md overflow-hidden mt-4 text-sm border-collapse; }
        .data-table th, .data-table td { @apply px-4 py-3 text-left border-b border-slate-100; }
        .data-table th    { @apply bg-slate-50 font-semibold text-slate-600 text-xs uppercase tracking-wide; }
        .inline-row-form  { @apply flex gap-2 items-center flex-wrap; }
        .inline-row-form input, .inline-row-form select { @apply w-auto mt-0; }

        /* Status badges */
        .badge-status    { @apply inline-block px-3 py-0.5 rounded-full text-xs font-medium capitalize; }
        .badge-pending    { @apply bg-amber-100 text-amber-800; }
        .badge-in_progress{ @apply bg-sky-100 text-sky-800; }
        .badge-resolved   { @apply bg-emerald-100 text-emerald-800; }
        .badge-rejected   { @apply bg-red-100 text-red-800; }

        /* Alerts */
        .alert         { @apply px-4 py-3 rounded-lg mb-4 text-sm; }
        .alert-success { @apply bg-emerald-50 text-emerald-800; }
        .alert-error   { @apply bg-red-50 text-red-800; }
        .alert ul      { @apply list-disc pl-5 m-0; }

        /* Filters */
        .filter-bar        { @apply flex gap-2 my-4 flex-wrap; }
        .filter-bar a      { @apply px-3 py-1 rounded-full bg-white text-xs shadow-sm text-slate-600 no-underline; }
        .filter-bar a.active { @apply bg-brand-primary text-white; }
        .filter-form       { @apply flex gap-3 items-center my-4 flex-wrap; }
        .filter-form input, .filter-form select { @apply w-auto mt-0; }

        /* Detail / timeline */
        .detail-card   { @apply bg-white p-6 rounded-2xl shadow-md; }
        .tracking-code { @apply text-slate-500 text-sm; }
        .timeline      { @apply list-none p-0 space-y-2; }
        .timeline li   { @apply bg-white px-4 py-3 rounded-lg shadow-sm text-sm border-l-4 border-brand-secondary; }

        /* Notifications */
        .notif-list    { @apply list-none p-0 space-y-2; }
        .notif-list li { @apply bg-white p-4 rounded-lg shadow-sm; }
        .notif-meta    { @apply text-slate-400 text-xs; }
      }
    </style>
