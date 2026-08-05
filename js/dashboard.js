const dashboardRoot = document.querySelector('[data-dashboard]');
const periodButtons = document.querySelectorAll('[data-dashboard-period]');
const metricsRoot = document.querySelector('[data-dashboard-metrics]');
const chartsRoot = document.querySelector('[data-dashboard-charts]');
const dashboardStatus = document.querySelector('[data-dashboard-status]');

let activePeriod = 'month';

function setDashboardStatus(message, type = 'loading') {
  if (!dashboardStatus) {
    return;
  }

  dashboardStatus.className = `dashboard-status dashboard-status--${type}`;
  dashboardStatus.textContent = message;
  dashboardStatus.hidden = false;
}

function hideDashboardStatus() {
  if (dashboardStatus) {
    dashboardStatus.hidden = true;
  }
}

function formatChartValue(value, type) {
  const number = Number(value || 0);

  if (type === 'money') {
    return new Intl.NumberFormat('es-MX', {
      style: 'currency',
      currency: 'MXN',
      maximumFractionDigits: 0,
    }).format(number);
  }

  return new Intl.NumberFormat('es-MX').format(number);
}

function renderMetrics(metrics = []) {
  metricsRoot.innerHTML = '';

  metrics.forEach((metric) => {
    const article = document.createElement('article');
    const label = document.createElement('span');
    const value = document.createElement('strong');
    const hint = document.createElement('small');

    article.className = 'metric-card dashboard-metric';
    label.textContent = metric.label;
    value.textContent = metric.value;
    hint.textContent = metric.hint;

    article.append(label, value, hint);
    metricsRoot.appendChild(article);
  });
}

function renderChart(chart) {
  const card = document.createElement('article');
  const header = document.createElement('div');
  const title = document.createElement('h2');
  const chartBody = document.createElement('div');
  const rows = chart.rows || [];
  const maxValue = Math.max(...rows.map((row) => Number(row.value || 0)), 0);

  card.className = 'dashboard-chart-card';
  header.className = 'dashboard-chart-header';
  chartBody.className = 'dashboard-bars';
  title.textContent = chart.title;
  header.appendChild(title);

  if (!rows.length || maxValue === 0) {
    const empty = document.createElement('p');
    empty.className = 'dashboard-chart-empty';
    empty.textContent = 'Sin datos para este periodo.';
    card.append(header, empty);
    return card;
  }

  rows.forEach((row) => {
    const item = document.createElement('div');
    const top = document.createElement('div');
    const label = document.createElement('span');
    const value = document.createElement('strong');
    const track = document.createElement('div');
    const bar = document.createElement('div');
    const width = Math.max(5, Math.round((Number(row.value || 0) / maxValue) * 100));

    item.className = 'dashboard-bar-item';
    top.className = 'dashboard-bar-top';
    label.textContent = row.label || 'Sin etiqueta';
    value.textContent = formatChartValue(row.value, chart.value_type);
    track.className = 'dashboard-bar-track';
    bar.className = 'dashboard-bar-fill';
    bar.style.width = `${width}%`;

    top.append(label, value);
    track.appendChild(bar);
    item.append(top, track);
    chartBody.appendChild(item);
  });

  card.append(header, chartBody);
  return card;
}

function renderCharts(charts = []) {
  chartsRoot.innerHTML = '';

  charts.forEach((chart) => {
    chartsRoot.appendChild(renderChart(chart));
  });
}

function setActivePeriod(period) {
  activePeriod = period;

  periodButtons.forEach((button) => {
    button.classList.toggle('is-active', button.dataset.dashboardPeriod === period);
  });
}

async function loadDashboard(period = activePeriod) {
  if (!dashboardRoot) {
    return;
  }

  setActivePeriod(period);
  setDashboardStatus('Cargando información del dashboard...');

  const url = new URL(dashboardRoot.dataset.endpoint, window.location.href);
  url.searchParams.set('period', activePeriod);

  try {
    const response = await fetch(url, {
      headers: {
        Accept: 'application/json',
      },
    });
    const payload = await response.json();

    if (!response.ok || !payload.ok) {
      throw new Error(payload.message || 'No se pudo cargar el dashboard.');
    }

    renderMetrics(payload.metrics || []);
    renderCharts(payload.charts || []);
    hideDashboardStatus();

    if (payload.message) {
      setDashboardStatus(payload.message, 'info');
    }
  } catch (error) {
    setDashboardStatus(error.message || 'No se pudo cargar el dashboard.', 'error');
  }
}

periodButtons.forEach((button) => {
  button.addEventListener('click', () => {
    loadDashboard(button.dataset.dashboardPeriod);
  });
});

loadDashboard(activePeriod);
