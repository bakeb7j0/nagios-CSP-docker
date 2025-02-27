if (theme == 'neptune') {
    // --background: hsl(213.33, 32.14%, 10.98%);
    var background = "#131B25";
    // --foreground: hsl(214.29, 30.43%, 90.98%);
    var foreground = "#E1E7EF";
    // --border: hsl(213.85, 31.71%, 24.12%);
    // Border is same as muted
    var border = "#2a3b51";

    // --muted-foreground: hsl(215, 20.2%, 65.1%);
    var mutedForeground = "#94a3b8";
} else if (theme == 'neptunelight' || 'neptunecolorblind'){
    // --background: hsl(213.33, 32.14%, 10.98%);
    var background = "#FCFCFC";
    // --foreground: hsl(214.29, 30.43%, 90.98%);
    var foreground = "#1A1A1A";
    // --border: hsl(213.85, 31.71%, 24.12%);
    // Border is same as muted
    var border = "#D6D6D6";

    // --muted-foreground: hsl(215, 20.2%, 65.1%);
    var mutedForeground = "#3d3d3d";
}
const radius = 4;

Highcharts.setOptions({
  colors: [
    "#DDDF0D",
    "#7798BF",
    "#55BF3B",
    "#DF5353",
    "#aaeeee",
    "#ff0066",
    "#eeaaee",
    "#55BF3B",
    "#DF5353",
    "#7798BF",
    "#aaeeee",
  ],
  chart: {
    backgroundColor: background,
    borderWidth: 0,
    borderRadius: 0,
    plotBackgroundColor: null,
    plotShadow: !1,
    plotBorderWidth: 0,
    resetZoomButton: {
      theme: {
        height: 15,
        r: "var(--radius)",
        stroke: 'transparent',
        fill: 'transparent',
        states: {
          hover: {
            fill: 'var(--secondary)'
          },
          select: {
            fill: 'var(--secondary)'
          }
        },
        style: {
          cursor: 'pointer',
          color: 'var(--secondary-foreground)'
        }
      }
    }
  },
  title: {
    style: {
      color: foreground,
      font: "16px Lucida Grande, Lucida Sans Unicode, Verdana, Arial, Helvetica, sans-serif",
    },
  },
  subtitle: {
    style: {
      color: mutedForeground,
      font: "12px Lucida Grande, Lucida Sans Unicode, Verdana, Arial, Helvetica, sans-serif",
    },
  },
  xAxis: {
    gridLineWidth: 0,
    lineColor: "#999",
    tickColor: "#999",
    labels: {
      style: {
        color: mutedForeground,
        fontWeight: "bold",
      },
    },
    title: {
      style: {
        color: foreground,
        font: "bold 12px Lucida Grande, Lucida Sans Unicode, Verdana, Arial, Helvetica, sans-serif",
      },
    },
  },
  yAxis: {
    alternateGridColor: null,
    minorTickInterval: null,
    gridLineColor: "rgba(255, 255, 255, .1)",
    minorGridLineColor: "rgba(255,255,255,0.07)",
    lineWidth: 0,
    tickWidth: 0,
    labels: {
      style: {
        color: mutedForeground,
        fontWeight: "bold",
      },
    },
    title: {
      style: {
        color: foreground,
        font: "bold 12px Lucida Grande, Lucida Sans Unicode, Verdana, Arial, Helvetica, sans-serif",
      },
    },
  },
  legend: {
    backgroundColor: 'transparent',
    itemStyle: {
      color: mutedForeground,
    },
    itemHoverStyle: {
      color: foreground,
    },
    itemHiddenStyle: {
      color: mutedForeground,
    },
    title: {
      style: {
        color: foreground,
      },
    },
  },
  labels: {
    style: {
      color: "#CCC",
    },
  },
  tooltip: {
    backgroundColor: "var(--input)",
    borderWidth: 1,
    borderRadius: radius,
    borderColor: "var(--border)",
    style: {
      color: foreground,
    },
  },
  plotOptions: {
    series: {
      dataLabels: {
        color: foreground,
      },
      nullColor: "#444444",
    },
    pie: {
      dataLabels: {
        color: foreground,
        connectorColor: foreground,
        style: {
          textOutline: "none",
        }
      },
      nullColor: mutedForeground,
    },
    line: {
      dataLabels: {
        color: "#CCC",
      },
      marker: {
        lineColor: "#333",
      },
    },
    spline: {
      marker: {
        lineColor: "#333",
      },
    },
    scatter: {
      marker: {
        lineColor: "#333",
      },
    },
    candlestick: {
      lineColor: "white",
    },
  },
  toolbar: {
    itemStyle: {
      color: "#CCC",
    },
  },
  navigation: {
    menuStyle: {
      background: "var(--input)",
      borderRadius: "var(--radius)",
      "box-shadow": "none",
      borderColor: "var(--border)",
    },
    menuItemStyle: {
      color: foreground,
    },
    menuItemHoverStyle: {
      background: "var(--border)",
    },
    buttonOptions: {
      symbolStroke: mutedForeground,
      theme: {
        r: radius,
        fill: background,
        symbolStroke: "var(--border)",
        states: {
          hover: {
            fill: "var(--secondary-hover)",
          },
          select: {
            fill: "var(--secondary-hover)",
          },
        },
      },
    },
  },
  rangeSelector: {
    buttonTheme: {
      fill: {
        linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
        stops: [
          [0.4, "#888"],
          [0.6, "#555"],
        ],
      },
      stroke: "#000000",
      style: {
        color: "#CCC",
        fontWeight: "bold",
      },
      states: {
        hover: {
          fill: {
            linearGradient: {x1: 0, y1: 0, x2: 0, y2: 1 },
            stops: [
              [0.4, "#BBB"],
              [0.6, "#888"],
            ],
          },
          stroke: "#000000",
          style: {
            color: "white",
          },
        },
        select: {
          fill: {
            linearGradient: {x1: 0, y1: 0, x2: 0, y2: 1 },
            stops: [
              [0.1, "#000"],
              [0.3, "#333"],
            ],
          },
          stroke: "#000000",
          style: {
            color: "yellow",
          },
        },
      },
    },
    inputStyle: {
      backgroundColor: "#333",
      color: "silver",
    },
    labelStyle: {
      color: "silver",
    },
  },
  navigator: {
    handles: {
      backgroundColor: "#666",
      borderColor: "#AAA",
    },
    outlineColor: "#CCC",
    maskFill: "rgba(16, 16, 16, 0.5)",
    series: {
      color: "#7798BF",
      lineColor: "#A6C7ED",
    },
  },
  scrollbar: {
    barBackgroundColor: {
      linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
      stops: [
        [0.4, "#888"],
        [0.6, "#555"],
      ],
    },
    barBorderColor: "#CCC",
    buttonArrowColor: "#CCC",
    buttonBackgroundColor: {
      linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
      stops: [
        [0.4, "#888"],
        [0.6, "#555"],
      ],
    },
    buttonBorderColor: "#CCC",
    rifleColor: "#FFF",
    trackBackgroundColor: {
      linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
      stops: [
        [0, "#000"],
        [1, "#333"],
      ],
    },
    trackBorderColor: "#666",
  },
});
