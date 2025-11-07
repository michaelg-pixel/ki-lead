        /* VIDEO TABS - IMMER SICHTBAR & PROMINENT */
        .video-tabs {
            background: linear-gradient(180deg, #0a0a16, #1a1532);
            border-top: 2px solid var(--border);
            padding: 20px;
            display: flex;
            gap: 12px;
            overflow-x: auto;
            overflow-y: hidden; /* KEIN vertikaler Scrollbalken */
            flex-wrap: wrap;
            min-height: 80px; /* Fixe Höhe für Sichtbarkeit */
            -webkit-overflow-scrolling: touch; /* Smooth scrolling */
        }
        
        /* Scrollbar verstecken (optional) */
        .video-tabs::-webkit-scrollbar {
            height: 0;
            width: 0;
        }
        
        .video-tab {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 24px;
            background: rgba(168, 85, 247, 0.08);
            border: 2px solid rgba(168, 85, 247, 0.2);
            border-radius: 12px;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
        }