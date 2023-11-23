let outputMatrix = [];

// For perfect squares only
foreach(let row in matrix) {
    let matrixEnd = matrix[row].length;
    foreach(let column in matrix[row]) {
        outputMatrix[matrixEnd - row][row] = matrix[row][column];
    }
}
