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
function stars(n) { return "★".repeat(n) + "☆".repeat(5 - n); }

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
        <span className="r-star">{stars(movie.stars)}</span>
        <span className="r-dur">⏱ {formatDuration(movie.duration)}</span>
        {coming && <span className="r-soon">COMING SOON</span>}
      </a>
      <div className="r-card-body">
        <h3>{movie.title}</h3>
        <p className="r-meta">{movie.cinema} · {formatDate(movie.date)} · {formatTime(movie.showtime)}</p>
        <div className="r-tags">
          <span className="r-tag">{movie.genre}</span>
          <span className="r-tag gold">CBFC {movie.certificate}</span>
        </div>
        <p className="r-desc">{movie.description}</p>
        <div className="r-actions">
          {coming
            ? <a className="r-btn ghost" href={`../movie_details.php?id=${movie.id}`}>Details</a>
            : <a className="r-btn" href={`../booking.php?movie_id=${movie.id}`}>🎟 Book</a>}
        </div>
      </div>
    </article>
  );
}

/* ---- Toolbar ---- */
function Toolbar({ genres, query, setQuery, genre, setGenre, status, setStatus, sort, setSort }) {
  return (
    <div className="r-toolbar">
      <input className="r-search" type="search" placeholder="🔍  Search movies by title…"
             value={query} onChange={(e) => setQuery(e.target.value)} />
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
        <option value="title">Sort: Title (A–Z)</option>
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
      .then((data) => { setMovies(data.movies || []); setLoading(false); })
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
        <a className="r-logo" href="../index.php">Cine<span>Book</span></a>
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
