/** @type {import('sequelize-cli').Migration} */
module.exports = {
	async up(queryInterface, Sequelize) {
		await queryInterface.createTable('data_request_constituents', {
			id: {
				allowNull: false,
				autoIncrement: true,
				primaryKey: true,
				type: Sequelize.INTEGER
			},
			fileType: {
				field: 'file_type',
				allowNull: false,
				type: Sequelize.INTEGER
			},
			dataRequestId: {
				field: 'data_request_id',
				type: Sequelize.INTEGER,
				name: 'dataRequestId',
				onUpdate: 'CASCADE',
				onDelete: 'CASCADE',
				references: { model: 'data_requests', key: 'id' },
				allowNull: true
			},
			label: {
				type: Sequelize.STRING
			},
			path: {
				type: Sequelize.STRING
			},
			accepted: {
				type: Sequelize.BOOLEAN,
				allowNull: true
			},
			created_at: {
				allowNull: false,
				type: Sequelize.DATE
			},
			updated_at: {
				allowNull: false,
				type: Sequelize.DATE
			}
		})
	},
	async down(queryInterface) {
		await queryInterface.dropTable('data_request_constituents')
	}
}
