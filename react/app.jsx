/* ============================================================
   CineBook — React Edition (ADDITIONAL VERSION / modern stack)

   A single-page movie browser built with React + Hooks. It fetches
   movie data from a JSON API (../api/movies.php) that reads the same
   MySQL database as the traditional PHP base version, then provides
   client-side live search, genre + status filtering and sorting —
   all without a full page reload.
   ============================================================ */

const { useState, useEffect, useMemo } = React;

const API_URL = "../api/movies.php";
const TICKET_PRICE = 12.5;

function formatDate(iso) {
  if (!iso) return "";
  const d = new Date(iso + "T00:00:00");
  return d.toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
}
function formatTime(t) {
  if (!t) return "";
  const [h, m] = t.split(":");
  const hour = ((+h + 11) % 12) + 1;
  return `${hour}:${m} ${+h >= 12 ? "PM" : "AM"}`;
}
function formatDuration(min) {
  const h = Math.floor(min / 60), m = min % 60;
  return (h ? h + "h " : "") + m + "m";
}
/* ---- Inline SVG icons (match the base-site icon set) ---- */
function Icon({ name, size = 16 }) {
  const common = {
    width: size, height: size, viewBox: "0 0 24 24", className: "r-ico", "aria-hidden": true,
    fill: "none", stroke: "currentColor", strokeWidth: 2, strokeLinecap: "round", strokeLinejoin: "round"
  };
  switch (name) {
    case "search":
      return <svg {...common}><circle cx="11" cy="11" r="7" /><line x1="21" y1="21" x2="16.6" y2="16.6" /></svg>;
    case "clock":
      return <svg {...common}><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg>;
    case "ticket":
      return <svg {...common}><path d="M4 6h16a1 1 0 0 1 1 1v3a2 2 0 0 0 0 4v3a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-3a2 2 0 0 0 0-4V7a1 1 0 0 1 1-1z" /><path d="M12 6.8v1.5M12 9.7v1.5M12 12.6v1.5M12 15.5v1.5" /></svg>;
    default:
      return null;
  }
}

/* ---- Popcorn rating (same flat popcorn as the base site) ---- */
function Popcorn({ filled }) {
  return (
    <svg className={"r-pop" + (filled ? "" : " empty")} viewBox="0 0 36 36" aria-hidden="true">
      <path fill="#EA3943" d="M6.5 13h23l-1.9 18.1a1.5 1.5 0 0 1-1.49 1.35H9.89a1.5 1.5 0 0 1-1.49-1.35L6.5 13z" />
      <path fill="#fff" d="M9.8 13h3.27l.86 18.45h-2.6zM16.36 13h3.28l-.28 18.45h-2.72zM22.93 13h3.27l-1.4 18.45h-2.6z" />
      <g fill="#F8C517">
        <path d="M6.6 13.4C4.6 8.4 8.4 4 11.6 4.6 12.8 1.8 15.4 1 18 1s4.6.8 5.8 3.6C27 4 30.8 8.4 28.8 13.4z" />
        <circle cx="9.2" cy="12.4" r="3.1" /><circle cx="26.8" cy="12.6" r="3" />
        <circle cx="12" cy="9.6" r="3.5" /><circle cx="16" cy="6.5" r="3.9" />
        <circle cx="20.6" cy="6.3" r="3.8" /><circle cx="24.4" cy="9.4" r="3.3" />
        <circle cx="14.6" cy="9" r="3.1" /><circle cx="18.6" cy="9.6" r="3.3" />
        <circle cx="22.2" cy="9.4" r="3.1" />
      </g>
      <g fill="none" stroke="#E2941B" strokeWidth="1.3" strokeLinecap="round">
        <path d="M12.4 9c.7-.5 1.4-.2 1.5.6" /><path d="M18.2 6.4c.8-.4 1.5 0 1.5.9" /><path d="M22.5 9.2c.7-.5 1.5-.2 1.6.6" />
      </g>
    </svg>
  );
}
function PopcornRating({ n }) {
  n = Math.max(0, Math.min(5, n | 0));
  if (n <= 0) return null;
  return (
    <span className="r-rating" title={n + " / 5"} aria-label={"Rated " + n + " out of 5"}>
      {[1, 2, 3, 4, 5].map((i) => <Popcorn key={i} filled={i <= n} />)}
    </span>
  );
}

/* ---- Movie card ---- */
function MovieCard({ movie }) {
  const posterStyle = movie.poster
    ? { backgroundImage: `url(../images/posters/${encodeURIComponent(movie.poster)})` }
    : undefined;
  const coming = movie.status === "coming";
  return (
    <article className="r-card">
      <a className={"r-poster" + (movie.poster ? " has-img" : "")}
         data-genre={movie.genre} style={posterStyle}
         href={coming ? `../movie_details.php?id=${movie.id}` : `../booking.php?movie_id=${movie.id}`}>
        {!coming && movie.stars > 0 && <PopcornRating n={movie.stars} />}
        <span className="r-dur"><Icon name="clock" size={12} /> {formatDuration(movie.duration)}</span>
        {coming && <span className="r-soon">COMING SOON</span>}
      </a>
      <div className="r-card-body">
        <h3>{movie.title}</h3>
        <p className="r-meta">{movie.cinema} · {formatDate(movie.date)} · {formatTime(movie.showtime)}</p>
        <div className="r-tags">
          <span className="r-tag">{movie.genre}</span>
          <span className="r-tag gold">Rating: {movie.certificate}</span>
        </div>
        <p className="r-desc">{movie.description}</p>
        <div className="r-actions">
          {coming
            ? <a className="r-btn ghost" href={`../movie_details.php?id=${movie.id}`}>Details</a>
            : <a className="r-btn" href={`../booking.php?movie_id=${movie.id}`}><Icon name="ticket" size={14} /> Book</a>}
        </div>
      </div>
    </article>
  );
}

/* ---- Toolbar ---- */
function Toolbar({ genres, query, setQuery, genre, setGenre, status, setStatus, sort, setSort }) {
  return (
    <div className="r-toolbar">
      <div className="r-search-wrap">
        <Icon name="search" size={16} />
        <input className="r-search" type="search" placeholder="Search movies by title…"
               value={query} onChange={(e) => setQuery(e.target.value)} />
      </div>
      <div className="r-tabs">
        {["all", "showing", "coming"].map((s) => (
          <button key={s} className={"r-tab" + (status === s ? " active" : "")} onClick={() => setStatus(s)}>
            {s === "all" ? "All" : s === "showing" ? "Now Showing" : "Coming Soon"}
          </button>
        ))}
      </div>
      <div className="r-chips">
        {["All", ...genres].map((g) => (
          <button key={g} className={"r-chip" + (genre === g ? " active" : "")} onClick={() => setGenre(g)}>{g}</button>
        ))}
      </div>
      <select className="r-sort" value={sort} onChange={(e) => setSort(e.target.value)}>
        <option value="date">Sort: Date</option>
        <option value="title">Sort: Title (A-Z)</option>
        <option value="stars">Sort: Rating</option>
        <option value="duration">Sort: Duration</option>
      </select>
    </div>
  );
}

/* ---- App ---- */
function App() {
  const [movies, setMovies] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const [query, setQuery] = useState("");
  const [genre, setGenre] = useState("All");
  const [status, setStatus] = useState("all");
  const [sort, setSort] = useState("date");

  useEffect(() => {
    fetch(API_URL)
      .then((r) => { if (!r.ok) throw new Error("HTTP " + r.status); return r.json(); })
      .then((data) => {
        if (data.status !== "ok" || !Array.isArray(data.movies)) throw new Error("Invalid movie service response");
        setMovies(data.movies);
        setLoading(false);
      })
      .catch((err) => {
        setError("Could not load movies. Make sure Apache + MySQL are running and the database is imported. (" + err.message + ")");
        setLoading(false);
      });
  }, []);

  const genres = useMemo(
    () => [...new Set(movies.map((m) => m.genre).filter(Boolean))].sort(), [movies]);

  const visible = useMemo(() => {
    let list = movies.filter((m) =>
      m.title.toLowerCase().includes(query.toLowerCase())
      && (genre === "All" || m.genre === genre)
      && (status === "all" || m.status === status));
    list = [...list].sort((a, b) => {
      if (sort === "title") return a.title.localeCompare(b.title);
      if (sort === "stars") return b.stars - a.stars;
      if (sort === "duration") return a.duration - b.duration;
      return (a.date + a.showtime).localeCompare(b.date + b.showtime);
    });
    return list;
  }, [movies, query, genre, status, sort]);

  return (
    <React.Fragment>
      <header className="r-header">
        <a className="r-logo" href="../index.php"><img src="cinebooklogo.png" alt="CineBok" /></a>
        <span className="r-badge">React Edition · Additional Version</span>
        <a className="r-home" href="../index.php">← Back to main site</a>
      </header>

      <main className="r-wrap">
        <section className="r-hero">
          <h1>Explore Movies</h1>
          <p>A modern React front-end reading live data from the same MySQL database.
             Search, filter and sort update instantly — no page reloads.</p>
        </section>

        {!loading && !error &&
          <Toolbar genres={genres} query={query} setQuery={setQuery} genre={genre} setGenre={setGenre}
                   status={status} setStatus={setStatus} sort={sort} setSort={setSort} />}

        {loading && <div className="r-status">Loading movies…</div>}
        {error && <div className="r-status error">{error}</div>}

        {!loading && !error &&
          <React.Fragment>
            <p className="r-count">{visible.length} movie(s) found</p>
            {visible.length > 0
              ? <div className="r-grid">{visible.map((m) => <MovieCard key={m.id} movie={m} />)}</div>
              : <div className="r-status">No movies match your search.</div>}
          </React.Fragment>}
      </main>

      <footer className="r-footer">
        <p>CineBook React Edition — IE4727 Additional Version (modern enhancement)</p>
      </footer>
    </React.Fragment>
  );
}

ReactDOM.createRoot(document.getElementById("root")).render(<App />);
