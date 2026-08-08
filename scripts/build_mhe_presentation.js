/**
 * Build MHE System Overview deck for FAST Logistics.
 * Run: node build_mhe_presentation.js
 */
const path = require("path");
const pptxgen = require("pptxgenjs");

const ROOT = path.join(__dirname, "..");
const OUT = path.join(ROOT, "docs", "presentations", "MHE-System-Overview-FAST.pptx");
const LOGO = path.join(ROOT, "docs", "presentations", "assets", "fyd-logo.png");

const C = {
  bg: "FAFAFA",
  surface: "FFFFFF",
  primary: "5B4FE9",
  primaryLight: "EDE9FE",
  text: "1E293B",
  muted: "737373",
  border: "E2E8F0",
  white: "FFFFFF",
};

const FOOTER = "FYD TECHNOLOGIES OPC | www.fydtech.io | Confidential";

function addLogo(slide, pptx) {
  slide.addImage({ path: LOGO, x: 0.35, y: 0.25, w: 1.4, h: 0.45 });
}

function addAccentBar(slide, pres) {
  slide.addShape(pres.ShapeType.rect, {
    x: 0.35,
    y: 0.95,
    w: 0.06,
    h: 0.45,
    fill: { color: C.primary },
    line: { color: C.primary, width: 0 },
  });
}

function addFooter(slide) {
  slide.addText(FOOTER, {
    x: 0.35,
    y: 5.15,
    w: 12.5,
    h: 0.3,
    fontSize: 9,
    color: C.muted,
    align: "left",
  });
}

function addTitle(slide, title, y = 0.88) {
  slide.addText(title, {
    x: 0.55,
    y,
    w: 12,
    h: 0.55,
    fontSize: 28,
    bold: true,
    color: C.primary,
    fontFace: "Calibri",
  });
}

function addBullets(slide, items, opts = {}) {
  const lines = items.map((t) => ({ text: t, options: { bullet: true, breakLine: true } }));
  slide.addText(lines, {
    x: opts.x ?? 0.55,
    y: opts.y ?? 1.55,
    w: opts.w ?? 12,
    h: opts.h ?? 3.5,
    fontSize: opts.fontSize ?? 14,
    color: C.text,
    fontFace: "Calibri",
    valign: "top",
  });
}

function addTable(slide, headers, rows, opts = {}) {
  const data = [
    headers.map((h) => ({
      text: h,
      options: { bold: true, fill: { color: C.primaryLight }, color: C.text },
    })),
    ...rows.map((row) =>
      row.map((cell) => ({ text: cell, options: { color: C.text } }))
    ),
  ];
  slide.addTable(data, {
    x: opts.x ?? 0.55,
    y: opts.y ?? 1.55,
    w: opts.w ?? 12,
    colW: opts.colW,
    fontSize: opts.fontSize ?? 11,
    fontFace: "Calibri",
    border: { type: "solid", color: C.border, pt: 0.5 },
    align: "left",
    valign: "middle",
  });
}

function contentSlide(pptx, title) {
  const slide = pptx.addSlide();
  slide.background = { color: C.bg };
  addLogo(slide, pptx);
  addAccentBar(slide, pptx);
  addTitle(slide, title);
  addFooter(slide);
  return slide;
}

function build() {
  const pptx = new pptxgen();
  pptx.layout = "LAYOUT_WIDE";
  pptx.author = "Everett Gaius S. Vergara";
  pptx.company = "FYD TECHNOLOGIES OPC";
  pptx.title = "MHE System Overview: PMS & Downtime";
  pptx.subject = "Prepared for FAST Logistics";

  // Slide 1 — Title
  {
    const slide = pptx.addSlide();
    slide.background = { color: C.bg };
    addLogo(slide, pptx);
    slide.addText("MHE System Overview", {
      x: 0.5,
      y: 1.8,
      w: 12.3,
      h: 0.8,
      fontSize: 36,
      bold: true,
      color: C.text,
      align: "center",
      fontFace: "Calibri",
    });
    slide.addText("Preventive Maintenance & Downtime", {
      x: 0.5,
      y: 2.55,
      w: 12.3,
      h: 0.5,
      fontSize: 22,
      color: C.primary,
      align: "center",
      fontFace: "Calibri",
    });
    slide.addShape(pptx.ShapeType.line, {
      x: 4.5,
      y: 3.2,
      w: 4.3,
      h: 0,
      line: { color: C.primary, width: 1.5 },
    });
    slide.addText("Prepared for FAST Logistics", {
      x: 0.5,
      y: 3.45,
      w: 12.3,
      h: 0.4,
      fontSize: 16,
      color: C.muted,
      align: "center",
      fontFace: "Calibri",
    });
    slide.addText(
      [
        { text: "Everett Gaius S. Vergara", options: { breakLine: true, bold: true } },
        { text: "Founder and Chief Technology Officer", options: { breakLine: true } },
        { text: "FYD TECHNOLOGIES OPC", options: { breakLine: true } },
        { text: "August 2026", options: { breakLine: true } },
      ],
      {
        x: 0.5,
        y: 4.2,
        w: 12.3,
        h: 1.2,
        fontSize: 14,
        color: C.text,
        align: "center",
        fontFace: "Calibri",
      }
    );
  }

  // Slide 2 — Purpose
  {
    const slide = contentSlide(pptx, "Purpose");
    slide.addText(
      "The MHE System is a web-based platform built for FAST Logistics to manage Material Handling Equipment (MHE) operations — replacing manual, paper-based processes with a centralized, auditable digital workflow.",
      {
        x: 0.55,
        y: 1.45,
        w: 12,
        h: 0.9,
        fontSize: 14,
        color: C.text,
        fontFace: "Calibri",
        fill: { color: C.primaryLight },
        margin: 8,
      }
    );
    addTable(
      slide,
      ["Module", "Description"],
      [
        ["Preventive Maintenance (PMS)", "Planned inspections via standardized checklists by accredited suppliers"],
        ["MHE Downtime", "Unplanned equipment failures and downtime incident tracking with corrective action plans"],
      ],
      { y: 2.5, colW: [3.5, 8.5] }
    );
    addBullets(
      slide,
      [
        "Accountability across suppliers and FAST teams",
        "Standardized procedures across all accredited suppliers",
        "Full audit trail on every transaction",
        "Real-time dashboards, compliance reports, and KPIs",
      ],
      { y: 3.85, h: 1.2, fontSize: 13 }
    );
  }

  // Slide 3 — Stakeholders
  {
    const slide = contentSlide(pptx, "Stakeholders & Process Owners");
    addTable(
      slide,
      ["Organization / Role", "Responsibility"],
      [
        ["FAST Logistics", "Client / system owner"],
        ["Accredited Suppliers (Toyota, Global, Boeing)", "Perform inspections and corrective actions"],
        ["FYD TECHNOLOGIES OPC", "System developer and technology partner"],
        ["FAST Administrator", "Masters, review, confirm action plans, reports, downtime posting"],
        ["Supplier User", "PMS checklists, action plans, downtime corrective actions"],
        ["Supplier Incharge", "Receives downtime notification on post; coordinates supplier response"],
      ],
      { y: 1.5, colW: [4.2, 7.8], fontSize: 11 }
    );
    slide.addText("Data scope: each user sees only their assigned supplier and FAST sites.", {
      x: 0.55,
      y: 4.75,
      w: 12,
      h: 0.35,
      fontSize: 12,
      italic: true,
      color: C.muted,
      fontFace: "Calibri",
    });
  }

  // Slide 4 — Platform
  {
    const slide = contentSlide(pptx, "Platform Overview");
    addTable(
      slide,
      ["", "mhe-pms (Active)", "Eagle Eye / fsc_web (Legacy)"],
      [
        ["Stack", "Laravel 12, PHP 8.5+, MariaDB", "Laravel 9"],
        ["PMS", "Built-in", "Not included"],
        ["MHE Downtime", "/mhe-downtimes", "/mhes (mod 46)"],
        ["Reports", "PMS + MHE Summary/Utilization/Uptime", "Summary, Utilization, Uptime"],
        ["Status", "Primary system going forward", "Being phased out on go-live"],
      ],
      { y: 1.5, colW: [2.2, 4.9, 4.9], fontSize: 10 }
    );
    addBullets(
      slide,
      [
        "Architecture: Browser → Routes → Controller → Service → Model → Database",
        "Done: Geo masters, downtime CRUD, action plans, Eagle Eye import (8,500+ records)",
        "Pending: Hide legacy downtime module on go-live",
      ],
      { y: 4.0, h: 1.1, fontSize: 12 }
    );
  }

  // Slide 5 — Shared Foundation
  {
    const slide = contentSlide(pptx, "Shared Foundation — Master Data");
    slide.addText(
      "Both PMS and MHE Downtime share master data, maintained exclusively by FAST Administrators.",
      { x: 0.55, y: 1.45, w: 12, h: 0.45, fontSize: 13, color: C.text, fontFace: "Calibri" }
    );
    addTable(
      slide,
      ["Group", "Masters", "Used by"],
      [
        ["Organization", "Regions, Districts, Sites, Suppliers, Users", "PMS + Downtime"],
        ["Equipment", "MHE Types, MHE Categories, MHE Inventories", "PMS + Downtime"],
        ["PMS-specific", "Checklist Groups, Checklist Items", "PMS only"],
      ],
      { y: 2.05, colW: [2.2, 6.3, 3.5] }
    );
    addBullets(
      slide,
      [
        "MHE Inventories link every unit to a site and accredited supplier",
        "Checklist Builder defines the dynamic inspection form per MHE type",
        "Compliance basis: 100% of all MHE units must be covered",
      ],
      { y: 3.85, h: 1.2, fontSize: 13 }
    );
  }

  // Slide 6 — PMS Workflow
  {
    const slide = contentSlide(pptx, "Preventive Maintenance (PMS) — Workflow");
    addTable(
      slide,
      ["Step", "Owner", "Action"],
      [
        ["1", "Supplier User", "Create PMS (Draft) — site, MHE type, unit, technician"],
        ["2", "Supplier User", "Complete checklist — Good or No Good (+ remarks if No Good)"],
        ["3", "Supplier User", "Submit (Forward for Approval) — record becomes read-only"],
        ["4", "System", "Auto-evaluate: all Good → No Findings; any No Good → With Findings"],
        ["5", "Supplier User", "Create Action Plans for each finding"],
        ["6", "Supplier User", "Add progress comments; mark Action Plan as Implemented"],
        ["7", "FAST Administrator", "Review in confirmation queue"],
        ["8", "FAST Administrator", "Confirm (closed) or Reject (supplier revises)"],
      ],
      { y: 1.45, colW: [0.6, 2.2, 9.2], fontSize: 10 }
    );
    slide.addText("Demo: PMS module", {
      x: 0.55,
      y: 5.0,
      w: 4,
      h: 0.3,
      fontSize: 10,
      italic: true,
      color: C.primary,
      fontFace: "Calibri",
    });
  }

  // Slide 7 — PMS Roles & Statuses
  {
    const slide = contentSlide(pptx, "PMS — Roles, Statuses & Responsibilities");
    addTable(
      slide,
      ["PMS Status", "Set by", "Meaning"],
      [
        ["Draft", "Supplier", "Editable; not yet submitted"],
        ["No Findings", "System", "All checklist items marked Good"],
        ["With Findings", "System", "One or more items marked No Good"],
        ["Cancelled", "FAST Admin", "Voided; read-only"],
      ],
      { y: 1.45, colW: [2.2, 1.5, 8.3], fontSize: 10 }
    );
    addTable(
      slide,
      ["Action Plan Status", "Set by", "Meaning"],
      [
        ["Pending", "Supplier", "Work in progress"],
        ["Waiting for FAST Confirmation", "Supplier", "Marked Implemented"],
        ["Confirmed / Rejected", "FAST Admin", "Verified or sent back for revision"],
      ],
      { y: 3.15, colW: [2.8, 1.5, 7.7], fontSize: 10 }
    );
    slide.addText(
      "Key rules: remarks required on No Good · submitted records read-only · FAST cannot edit supplier responses",
      { x: 0.55, y: 4.75, w: 12, h: 0.35, fontSize: 10, color: C.muted, fontFace: "Calibri" }
    );
  }

  // Slide 8 — MHE Downtime Workflow
  {
    const slide = contentSlide(pptx, "MHE Downtime — Workflow");
    addTable(
      slide,
      ["Step", "Owner", "Action"],
      [
        ["1", "FAST Administrator", "Create downtime record (Draft) — site, unit, incident, root cause"],
        ["2", "FAST Administrator", "Post downtime — status Posted; email to Supplier Incharge"],
        ["3", "Supplier Incharge", "Receives notification; coordinates supplier response"],
        ["4", "Supplier User", "Create corrective Action Plans on posted downtime"],
        ["5", "Supplier User", "Add progress comments and attachments"],
        ["6", "Supplier User", "Mark Action Plan as Implemented"],
        ["7", "FAST Administrator", "Review in Downtime Action Plan Confirmation queue"],
        ["8", "FAST Administrator", "Confirm (closed) or Reject (supplier revises)"],
      ],
      { y: 1.45, colW: [0.6, 2.4, 9.0], fontSize: 10 }
    );
    slide.addText("Demo: MHE Downtimes module", {
      x: 0.55,
      y: 5.0,
      w: 5,
      h: 0.3,
      fontSize: 10,
      italic: true,
      color: C.primary,
      fontFace: "Calibri",
    });
  }

  // Slide 9 — Downtime Roles & Notifications
  {
    const slide = contentSlide(pptx, "MHE Downtime — Roles, Statuses & Notifications");
    addTable(
      slide,
      ["Downtime Status", "Set by", "Meaning"],
      [
        ["Draft", "FAST Admin", "Editable; supplier cannot create action plans"],
        ["Posted", "FAST Admin", "Supplier can create action plans; email sent"],
        ["Cancelled", "FAST Admin", "Voided; read-only"],
      ],
      { y: 1.45, colW: [2.0, 1.8, 8.2], fontSize: 10 }
    );
    addBullets(
      slide,
      [
        "On Post: system resolves Supplier Incharge by site + supplier → sends email notification",
        "Supplier User: action plans ONLY on Posted downtimes",
        "Separate module: Downtime Action Plan Confirmation (distinct from PMS)",
        "Legacy Eagle Eye used email + SMS; mhe-pms uses email",
      ],
      { y: 3.0, h: 1.5, fontSize: 12 }
    );
    slide.addText("Demo: Post downtime + confirmation queue", {
      x: 0.55,
      y: 4.85,
      w: 6,
      h: 0.3,
      fontSize: 10,
      italic: true,
      color: C.primary,
      fontFace: "Calibri",
    });
  }

  // Slide 10 — Reports & Dashboards
  {
    const slide = contentSlide(pptx, "Reports & Dashboards");
    addTable(
      slide,
      ["Category", "Reports"],
      [
        ["Dashboard", "KPI cards, pending FAST confirmations work queue, MHE Uptime dashboard"],
        ["PMS Reports", "PMS Summary, Checklist Findings, Action Plan, Pending Confirmation, Supplier Compliance"],
        ["MHE Reports", "MHE Summary, MHE Utilization, MHE Uptime"],
        ["Export", "Excel (.xlsx) and PDF on all reports"],
      ],
      { y: 1.5, colW: [2.2, 9.8], fontSize: 11 }
    );
    slide.addText("Demo: Dashboard → Reports menu → filters and export", {
      x: 0.55,
      y: 4.5,
      w: 8,
      h: 0.3,
      fontSize: 10,
      italic: true,
      color: C.primary,
      fontFace: "Calibri",
    });
  }

  // Slide 11 — Closing
  {
    const slide = pptx.addSlide();
    slide.background = { color: C.bg };
    addLogo(slide, pptx);
    slide.addText("Thank You", {
      x: 0.5,
      y: 1.5,
      w: 12.3,
      h: 0.7,
      fontSize: 36,
      bold: true,
      color: C.primary,
      align: "center",
      fontFace: "Calibri",
    });
    slide.addText("Questions?", {
      x: 0.5,
      y: 2.2,
      w: 12.3,
      h: 0.4,
      fontSize: 20,
      color: C.muted,
      align: "center",
      fontFace: "Calibri",
    });
    addBullets(
      slide,
      [
        "Done: Geo masters, downtime CRUD, action plans, Eagle Eye import (8,500+ records)",
        "Pending: Legacy Eagle Eye downtime module cutover on go-live",
      ],
      { x: 2.5, y: 2.85, w: 8.3, h: 0.9, fontSize: 12 }
    );
    slide.addText(
      [
        { text: "Everett Gaius S. Vergara", options: { breakLine: true, bold: true } },
        { text: "Founder and Chief Technology Officer", options: { breakLine: true } },
        { text: "FYD TECHNOLOGIES OPC", options: { breakLine: true } },
        { text: "26F One Vertis Plaza, Vertis North, Quezon City 1105", options: { breakLine: true } },
        { text: "Mobile: +63 917 710 1995  |  Direct: +632 7618 2983", options: { breakLine: true } },
        { text: "www.fydtech.io  ·  www.fydesigns.ph  ·  www.collectimate.io", options: { breakLine: true } },
      ],
      {
        x: 0.5,
        y: 4.0,
        w: 12.3,
        h: 1.2,
        fontSize: 13,
        color: C.text,
        align: "center",
        fontFace: "Calibri",
      }
    );
  }

  return pptx.writeFile({ fileName: OUT });
}

build()
  .then(() => {
    console.log(`Generated: ${OUT}`);
  })
  .catch((err) => {
    console.error(err);
    process.exit(1);
  });
