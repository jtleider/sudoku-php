#!/usr/bin/env php
<?php
/**
 * A brute-force Sudoku solver.
 *
 * This program takes a Sudoku puzzle from standard input
 * and outputs a solution to the puzzle if one exists.
 * A Sudoku puzzle consists of a 9x9 grid in which each
 * cell contains a digit from 1 to 9. This grid is further
 * subdivided into 9 3x3 subgrids. A solution to a
 * Sudoku puzzle is an assignment of values to cells so
 * that no two cells in the same row, column, or subgrid
 * have the same value.
 *
 * This program expects the Sudoku grid to be input without spaces
 * in between numbers, with zeros entered for blank spaces,
 * and with one row per line. For example, this would be
 * valid input:
 * 000000706
 * 080900020
 * 001060500
 * 000054300
 * 090312040
 * 002890000
 * 003040200
 * 070003010
 * 108000000
 * The program will output a nicely printed grid with the
 * solution to the puzzle, if one exists; otherwise, it will
 * say there is no solution.
 *
 * The program solves a Sudoku puzzle by trying every possibility
 * for each blank cell until it either finds a solution to the
 * puzzle or exhausts all the possibilities.
 *
 */

/**
 * Solves a Sudoku puzzle represented by the two-dimensional array $grid
 * and returns a boolean value representing whether the puzzle could
 * be solved (note: we assume there are no conflicts such as the
 * same number occurring twice in a row in the input). The function
 * stores the solution to the puzzle in $grid.
 */
function solve(&$grid) {
	// Generate an intermediate grid containing the options for each cell.
	list($intGrid, $status) = genIntGrid($grid);
	if (!$status) {
		return false;
	}
	for ($i = 0; $i < 9; $i++) {
		for ($j = 0; $j < 9; $j++) {
			if (gettype($intGrid[$i][$j]) == "array") {
				// We have an array of options for this cell.
				if (count($intGrid[$i][$j]) == 0) {
					return false; // We've reached a dead end.
				}
				foreach ($intGrid[$i][$j] as $op) {
					$newGrid = $intGrid;
					$newGrid[$i][$j] = $op;
					if (solve($newGrid)) { // This option worked.
						for ($i2 = 0; $i2 < 9; $i2++) {
							for ($j2 = 0; $j2 < 9; $j2++) {
								$grid[$i2][$j2] = $newGrid[$i2][$j2];
							}
						}
						return true;
					}
				}
				return false; // We've reached a dead end.
			}
		}
	}
	// Every cell in the grid was already correctly filled in, so we are done.
	return true;
}

/**
 * Generates an intermediate grid in the solution of a Sudoku puzzle
 * represented by the two-dimensional array $grid. Returns
 * array($newGrid, $status), where $status is false if the
 * function encountered a dead end and true otherwise, and
 * where $newGrid is the intermediate grid produced by the
 * function. In $newGrid, each cell contains either a single
 * assigned value or an array of options indicating values
 * it could have without creating a conflict with another cell.
 */
function genIntGrid($grid) {
	$notUsedRow = array(); // Array of numbers not used so far in each row.
	for ($i = 0; $i < 9; $i++) {
		$notUsedRow[] = range(1, 9);
	}
	for ($i = 0; $i < 9; $i++) {
		for ($j = 0; $j < 9; $j++) {
			$notUsedRow[$i] = array_diff($notUsedRow[$i],
											array($grid[$i][$j]));
		}
	}
	$notUsedCol = array(); // Array of numbers not used so far in each column.
	for ($i = 0; $i < 9; $i++) {
		$notUsedCol[] = range(1, 9);
	}
	for ($i = 0; $i < 9; $i++) {
		for ($j = 0; $j < 9; $j++) {
			$notUsedCol[$j] = array_diff($notUsedCol[$j],
											array($grid[$i][$j]));
		}
	}
	$notUsedSgrid = array(); // Array of numbers not used so far in each subgrid.
	for ($i = 0; $i < 3; $i++) {
		$notUsedSgrid[$i] = array();
		for ($j = 0; $j < 3; $j++) {
			$notUsedSgrid[$i][$j] = range(1, 9);
		}
	}
	for ($i = 0; $i < 9; $i++) {
		for ($j = 0; $j < 9; $j++) {
			$notUsedSgrid[floor($i / 3)][floor($j / 3)] = array_diff($notUsedSgrid[floor($i / 3)][floor($j / 3)],
																		array($grid[$i][$j]));
		}
	}
	$intGrid = array(); // This is the intermediate grid we will return.
	for ($i = 0; $i < 9; $i++) {
		$intGrid[] = range(0, 8);
	}
	for ($i = 0; $i < 9; $i++) {
		for ($j = 0; $j < 9; $j++) {
			if (($grid[$i][$j] == 0) || (gettype($grid[$i][$j]) == "array")) {
				$intGrid[$i][$j] = array_intersect($notUsedRow[$i], $notUsedCol[$j],
													$notUsedSgrid[floor($i / 3)][floor($j / 3)]);
				if (!$intGrid[$i][$j]) {
					return array($intGrid, false);
				}
			} else {
				$intGrid[$i][$j] = $grid[$i][$j];
			}
		}
	}
	return array($intGrid, true);
}

/**
 * Displays user-friendly representation of a Sudoku puzzle
 * contained in the 2-dimensional array $grid.
 */
function displayGrid($grid) {
	for ($i = 0; $i < 9; $i++) {
		if ($i % 3 == 0) {
			echo str_repeat('-', 30) . "\n";
		}
		for ($j = 0; $j < 9; $j++) {
			if ($j % 3 == 0) {
				echo "|";
			}
			echo sprintf("%3s", $grid[$i][$j]);
		}
		echo "|\n";
	}
	echo str_repeat('-', 30) . "\n";
}

/**
 * Checks user input to ensure it is properly formatted. If it is not,
 * the function displays an error message and the program exits.
 * If the input is properly formatted but there is a conflict in the
 * input grid (we already have two cells in the same row, column, or
 * subgrid with the same value), then the function returns false. Otherwise,
 * it returns true.
 */
function checkInput($grid) {
	if (count($grid) != 9) {
		echo "ERROR: A Sudoku grid must contain 9 rows.\n";
		exit(1);
	}
	$usedRow = array();
	for ($i = 0; $i < 9; $i++) {
		$usedRow[] = array();
	}
	$usedCol = array();
	for ($j = 0; $j < 9; $j++) {
		$usedCol[] = array();
	}
	$usedSgrid = array();
	for ($i = 0; $i < 3; $i++) {
		$usedSgrid[$i] = array();
		for ($j = 0; $j < 3; $j++) {
			$usedSgrid[$i][] = array();
		}
	}
	$conflict = false;
	for ($i = 0; $i < 9; $i++) {
		if (count($grid[$i]) != 9) {
			echo sprintf("ERROR (row %d): A Sudoku grid must contain 9 columns.\n", $i + 1);
			exit(1);
		}
		for ($j = 0; $j < 9; $j++) {
			if (!(is_numeric($grid[$i][$j]))) {
				echo sprintf("ERROR (row %d, column %d): Each cell in a Sudoku grid " .
				"must contain an integer from 0 to 9.\n", $i + 1, $j + 1);
				exit(1);
			}
			if (in_array($grid[$i][$j], $usedRow[$i]) || in_array($grid[$i][$j], $usedCol[$j])
				|| in_array($grid[$i][$j], $usedSgrid[floor($i / 3)][floor($j / 3)])) {
				$conflict = true;
			}
			if ($grid[$i][$j] != 0) {
				$usedRow[$i][] = $grid[$i][$j];
				$usedCol[$j][] = $grid[$i][$j];
				$usedSgrid[floor($i / 3)][floor($j / 3)][] = $grid[$i][$j];
			}
		}
	}
	return !$conflict;
}
/**
 * Runs the program.
 */
function main() {
	$grid = array();
	echo "Enter Sudoku grid (no spaces between numbers, '0' for blanks, one "
	. "line for each row):\n";
	for ($i = 0; $i < 9; $i++) {
		// For each row, generate an array of the numbers on the row.
		$grid[] = str_split(rtrim(fgets(STDIN)));
	}

	$returnVal = checkInput($grid);
	echo "Original grid:\n";
	displayGrid($grid);
	if ($returnVal && solve($grid)) {
		echo "Solution:\n";
		displayGrid($grid);
	} else {
		echo "There is no solution.\n";
	}
}

main();
