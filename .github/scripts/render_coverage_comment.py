#!/usr/bin/env python3

from __future__ import annotations

import argparse
import subprocess
import sys
import xml.etree.ElementTree as ET
from dataclasses import dataclass
from pathlib import Path
from typing import Iterable


EPSILON = 1e-9


@dataclass
class CoverageMetrics:
    lines_covered: int = 0
    lines_valid: int = 0
    branches_covered: int | None = None
    branches_valid: int | None = None

    @property
    def line_rate(self) -> float | None:
        if self.lines_valid == 0:
            return None
        return self.lines_covered / self.lines_valid

    @property
    def branch_rate(self) -> float | None:
        if not self.has_branches:
            return None
        assert self.branches_covered is not None
        assert self.branches_valid is not None
        return self.branches_covered / self.branches_valid

    @property
    def has_branches(self) -> bool:
        return (
            self.branches_valid is not None
            and self.branches_valid > 0
            and self.branches_covered is not None
        )


@dataclass
class CoverageReport:
    total: CoverageMetrics
    files: dict[str, CoverageMetrics]
    file_index: dict[str, str]


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser()
    parser.add_argument("--coverage-file", required=True)
    parser.add_argument("--output", required=True)
    parser.add_argument("--title", required=True)
    parser.add_argument("--base-coverage-file")
    parser.add_argument("--base-label", default="base")
    parser.add_argument("--diff-base")
    parser.add_argument("--diff-head")
    parser.add_argument("--source-root")
    parser.add_argument("--threshold-medium", type=float, default=60.0)
    parser.add_argument("--threshold-high", type=float, default=80.0)
    return parser.parse_args()


def parse_int(value: str | None) -> int | None:
    if value is None or value == "":
        return None
    return int(value)


def normalize_path(path: str) -> str:
    return path.replace("\\", "/").lstrip("./")


def suffix_variants(path: str) -> list[str]:
    normalized = normalize_path(path)
    parts = [part for part in Path(normalized).parts if part not in ("", ".")]
    variants: list[str] = []
    for index in range(len(parts)):
        suffix = "/".join(parts[index:])
        if suffix and suffix not in variants:
            variants.append(suffix)
    return variants or [normalized]


def build_file_index(files: dict[str, CoverageMetrics]) -> dict[str, str]:
    index: dict[str, str] = {}
    collisions: set[str] = set()

    for filename in files:
        for variant in suffix_variants(filename):
            if variant in collisions:
                continue
            if variant in index and index[variant] != filename:
                del index[variant]
                collisions.add(variant)
                continue
            index[variant] = filename

    return index


def parse_branch_counts(value: str | None) -> tuple[int, int] | None:
    if not value or "(" not in value or "/" not in value:
        return None

    inside = value.split("(", 1)[1].rstrip(")")
    covered, valid = inside.split("/", 1)
    return int(covered), int(valid)


def parse_report(path: str) -> CoverageReport:
    root = ET.parse(path).getroot()

    total = CoverageMetrics(
        lines_covered=parse_int(root.attrib.get("lines-covered")) or 0,
        lines_valid=parse_int(root.attrib.get("lines-valid")) or 0,
        branches_covered=parse_int(root.attrib.get("branches-covered")),
        branches_valid=parse_int(root.attrib.get("branches-valid")),
    )

    line_hits: dict[str, dict[int, int]] = {}
    branch_hits: dict[str, dict[int, tuple[int, int]]] = {}

    for class_node in root.findall(".//class"):
        filename = class_node.attrib.get("filename")
        if not filename:
            continue

        normalized_filename = normalize_path(filename)
        file_lines = line_hits.setdefault(normalized_filename, {})
        file_branches = branch_hits.setdefault(normalized_filename, {})

        for line_node in class_node.findall("./lines/line"):
            line_number = parse_int(line_node.attrib.get("number"))
            hits = parse_int(line_node.attrib.get("hits"))

            if line_number is None or hits is None:
                continue

            file_lines[line_number] = max(file_lines.get(line_number, 0), hits)

            if line_node.attrib.get("branch") == "true":
                counts = parse_branch_counts(line_node.attrib.get("condition-coverage"))
                if counts is None:
                    continue

                existing = file_branches.get(line_number)
                if existing is None or counts[0] > existing[0]:
                    file_branches[line_number] = counts

    files: dict[str, CoverageMetrics] = {}
    for filename, hits_by_line in line_hits.items():
        branch_counts = branch_hits.get(filename, {})
        branches_valid = sum(total_count for _, total_count in branch_counts.values())
        branches_covered = sum(covered_count for covered_count, _ in branch_counts.values())

        files[filename] = CoverageMetrics(
            lines_covered=sum(1 for hits in hits_by_line.values() if hits > 0),
            lines_valid=len(hits_by_line),
            branches_covered=branches_covered if branch_counts else None,
            branches_valid=branches_valid if branch_counts else None,
        )

    return CoverageReport(total=total, files=files, file_index=build_file_index(files))


def resolve_changed_files(
    diff_base: str | None,
    diff_head: str | None,
    source_root: str | None,
) -> list[str]:
    command = ["git", "diff", "--name-only"]
    if diff_base and diff_head:
        command.extend([diff_base, diff_head])

    if source_root:
        command.extend(["--", source_root])

    result = subprocess.run(
        command,
        check=True,
        capture_output=True,
        text=True,
    )
    return [normalize_path(line) for line in result.stdout.splitlines() if line.strip()]


def lookup_metrics(report: CoverageReport, changed_file: str) -> CoverageMetrics | None:
    for variant in suffix_variants(changed_file):
        canonical = report.file_index.get(variant)
        if canonical is not None:
            return report.files[canonical]
    return None


def format_percent(rate: float | None) -> str:
    if rate is None:
        return "n/a"
    return f"{rate * 100:.1f}%"


def format_counts(covered: int, valid: int) -> str:
    return f"{covered} / {valid}"


def format_metric(metrics: CoverageMetrics | None, metric: str) -> str:
    if metrics is None:
        return "n/a"

    if metric == "lines":
        return f"{format_percent(metrics.line_rate)} ({format_counts(metrics.lines_covered, metrics.lines_valid)})"

    if metric == "branches":
        if not metrics.has_branches:
            return "n/a"
        assert metrics.branches_covered is not None
        assert metrics.branches_valid is not None
        return f"{format_percent(metrics.branch_rate)} ({format_counts(metrics.branches_covered, metrics.branches_valid)})"

    raise ValueError(f"Unsupported metric: {metric}")


def format_delta(current_rate: float | None, base_rate: float | None) -> str:
    if current_rate is None or base_rate is None:
        return "n/a"

    delta = (current_rate - base_rate) * 100
    if abs(delta) < 0.05:
        return "0.0 pp"
    return f"{delta:+.1f} pp"


def health_color(rate: float | None, threshold_medium: float, threshold_high: float) -> str:
    if rate is None:
        return "lightgrey"

    percent = rate * 100
    if percent >= threshold_high:
        return "brightgreen"
    if percent >= threshold_medium:
        return "yellow"
    return "critical"


def build_badge(line_rate: float | None, threshold_medium: float, threshold_high: float) -> str:
    percent = "n/a" if line_rate is None else f"{round(line_rate * 100)}%25"
    color = health_color(line_rate, threshold_medium, threshold_high)
    return (
        "![Code Coverage]"
        f"(https://img.shields.io/badge/Code%20Coverage-{percent}-{color}?style=flat)"
    )


def classify_change(current: CoverageMetrics | None, base: CoverageMetrics | None) -> str:
    if current is None:
        return "unknown"
    if base is None or base.line_rate is None:
        return "new"
    assert current.line_rate is not None
    delta = current.line_rate - base.line_rate
    if delta > EPSILON:
        return "improved"
    if delta < -EPSILON:
        return "regressed"
    return "unchanged"


def summarize_changes(statuses: Iterable[str]) -> str:
    counts = {"improved": 0, "regressed": 0, "unchanged": 0, "new": 0}
    for status in statuses:
        if status in counts:
            counts[status] += 1

    parts = [f"{counts['improved']} improved", f"{counts['regressed']} regressed"]
    if counts["new"]:
        parts.append(f"{counts['new']} new")
    if counts["unchanged"]:
        parts.append(f"{counts['unchanged']} unchanged")
    return ", ".join(parts)


def render_comment(args: argparse.Namespace) -> str:
    current_report = parse_report(args.coverage_file)
    base_report = parse_report(args.base_coverage_file) if args.base_coverage_file else None
    changed_files = resolve_changed_files(args.diff_base, args.diff_head, args.source_root)

    rows: list[tuple[str, CoverageMetrics | None, CoverageMetrics | None, str]] = []
    for changed_file in changed_files:
        current_metrics = lookup_metrics(current_report, changed_file)
        base_metrics = lookup_metrics(base_report, changed_file) if base_report else None

        if current_metrics is None and base_metrics is None:
            continue

        rows.append((changed_file, current_metrics, base_metrics, classify_change(current_metrics, base_metrics)))

    rows.sort(key=lambda row: row[0])

    total_lines = format_metric(current_report.total, "lines")
    total_branches = format_metric(current_report.total, "branches")

    lines = [f"### {args.title}", "", build_badge(current_report.total.line_rate, args.threshold_medium, args.threshold_high), ""]

    total_line = f"**Total line coverage:** {total_lines}"
    if base_report:
        total_line += f" ({format_delta(current_report.total.line_rate, base_report.total.line_rate)} vs {args.base_label})"
    lines.append(total_line)

    if current_report.total.has_branches:
        branch_line = f"**Total branch coverage:** {total_branches}"
        if base_report and base_report.total.branch_rate is not None:
            branch_line += f" ({format_delta(current_report.total.branch_rate, base_report.total.branch_rate)} vs {args.base_label})"
        lines.append(branch_line)

    lines.append("")

    if not rows:
        lines.append("No changed files from this PR matched the coverage report.")
        return "\n".join(lines).rstrip() + "\n"

    lines.append(
        f"**Changed files with coverage data:** {len(rows)}"
        + (f" ({summarize_changes(status for *_, status in rows)})" if base_report else "")
    )
    lines.append("")
    lines.append("<details>")
    lines.append(
        "<summary>"
        + (
            f"Show changed files compared to {args.base_label}"
            if base_report
            else "Show changed files"
        )
        + "</summary>"
    )
    lines.append("")

    header = ["File", "Lines"]
    divider = ["----", "-----"]

    if base_report:
        header.extend([f"Lines ({args.base_label})", "Delta"])
        divider.extend(["----------------", "-----"])

    if current_report.total.has_branches or (base_report and base_report.total.branch_rate is not None):
        header.append("Branches")
        divider.append("--------")
        if base_report:
            header.extend([f"Branches ({args.base_label})", "Delta"])
            divider.extend(["-------------------", "-----"])

    lines.append(" | ".join(header))
    lines.append(" | ".join(divider))

    show_branches = "Branches" in header
    for changed_file, current_metrics, base_metrics, _ in rows:
        row = [changed_file, format_metric(current_metrics, "lines")]

        if base_report:
            row.append(format_metric(base_metrics, "lines"))
            row.append(format_delta(current_metrics.line_rate if current_metrics else None, base_metrics.line_rate if base_metrics else None))

        if show_branches:
            row.append(format_metric(current_metrics, "branches"))
            if base_report:
                row.append(format_metric(base_metrics, "branches"))
                row.append(
                    format_delta(
                        current_metrics.branch_rate if current_metrics else None,
                        base_metrics.branch_rate if base_metrics else None,
                    )
                )

        lines.append(" | ".join(row))

    lines.append("")
    lines.append("</details>")

    return "\n".join(lines).rstrip() + "\n"


def main() -> int:
    args = parse_args()
    output_path = Path(args.output)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(render_comment(args), encoding="utf-8")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except subprocess.CalledProcessError as error:
        print(error.stderr or error, file=sys.stderr)
        raise
